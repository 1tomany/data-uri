# Managed Temporary Files for PHP

`1tomany/data-uri` safely materializes several common forms of input as managed temporary files:

- Data URLs defined by [RFC 2397](https://www.rfc-editor.org/rfc/rfc2397.html)
- Readable local files and `file://` URLs
- Public HTTP and HTTPS URLs
- Base64-encoded content
- Plain text

The resulting `TemporaryFile` can be uploaded to storage such as Amazon S3 or Cloudflare R2, passed to an LLM or parser, read as a stream, or converted back to Base64 or a data URL.

## Requirements

- PHP 8.4 or later
- The `fileinfo` extension

## Installation

```shell
composer require 1tomany/data-uri
```

## Quick start

```php
use OneToMany\DataUri\Contract\Enum\Type;
use OneToMany\DataUri\DataDecoder;

$decoder = new DataDecoder();

$file = $decoder->decode(
    'data:text/plain,Hello%2C%20world%21',
    Type::Txt,
    'hello.txt',
);

echo $file->getPath();
echo $file->read();

// Prefer deterministic cleanup when work is complete.
$file->delete();
```

`DataDecoder` is the public facade. Its methods return `TemporaryFileInterface`, implemented by `TemporaryFile`.

## Decoder API

### `decode()`

```php
$file = $decoder->decode(
    data: $pathUrlOrDataUri,
    type: null,
    name: null,
);
```

Parameters, in positional order:

1. `string|\Stringable $data`: a data URI, readable local path, `file://` URL, or HTTP(S) URL.
2. `string|Type|MediaType|null $type`: an optional media-type override. `null` uses declared metadata and then content detection.
3. `?string $name`: an optional original/display filename.

Source strings containing control or NULL characters are rejected. Unsupported PHP stream schemes are not opened implicitly.

### `decodeBase64()`

```php
$file = $decoder->decodeBase64(
    data: 'SGVsbG8sIHdvcmxkIQ==',
    type: Type::Txt,
    name: 'hello.txt',
);
```

Base64 is decoded strictly. Invalid Base64 or media types throw `InvalidArgumentException`.

### `decodeStream()`

```php
$stream = fopen('/incoming/upload.bin', 'rb');

try {
    $file = $decoder->decodeStream(
        stream: $stream,
        type: 'application/octet-stream',
        name: 'upload.bin',
    );
} finally {
    fclose($stream);
}
```

The caller retains ownership of the input stream. The decoder reads from its current position and does not close it.

### `decodeText()`

```php
$file = $decoder->decodeText(
    text: '**Hello, world!**',
    type: Type::Markdown,
    name: 'hello.md',
);
```

The media type must be textual. Known extensions are appended when the safe name does not already use them.

## Media types

`Type` remains a convenient registry of known file-type presets. `MediaType` preserves valid custom MIME types and parameters instead of collapsing them to `application/octet-stream`.

```php
use OneToMany\DataUri\MediaType;

$type = MediaType::fromString(
    'application/vnd.acme+json; charset=UTF-8',
);

$file = $decoder->decodeText('{}', $type, 'payload');

echo $file->getFormat();
// application/vnd.acme+json;charset=UTF-8
```

Media-type precedence is:

1. An explicit caller override.
2. A media type declared by a data URI or HTTP response.
3. `fileinfo` content detection.
4. `application/octet-stream`.

## Temporary-file lifecycle

Every decoded file is placed in its own randomly generated workspace. Directories use mode `0700` and files use mode `0600` on platforms that support POSIX permissions.

### Deterministic deletion

```php
$file->delete();
```

`delete()` is idempotent. It deletes only the exact owned file and removes its known workspace non-recursively if the directory is empty. It refuses to recursively remove unexpected files or directories.

The destructor performs the same cleanup on a best-effort basis, but deterministic deletion is preferable for long-running workers.

### Transfer ownership

```php
$path = $file->detach();
```

`detach()` disables automatic cleanup and transfers responsibility for the file and its workspace to the caller. This is useful when another component takes ownership of the path.

Manually constructing `TemporaryFile` creates an unmanaged object unless an exact direct-parent owned directory is explicitly supplied.

## Streaming and size limits

Paths, URLs, data URIs, and open streams are copied stream-to-stream rather than loaded into memory first. The plain-text and Base64 convenience methods necessarily begin with caller-provided strings; Base64 is rejected from its encoded length when it cannot fit, then checked again after strict decoding. The default maximum decoded size is 100 MiB.

```php
use OneToMany\DataUri\DataDecoder;

$decoder = new DataDecoder(
    temporaryDirectory: '/absolute/private/tmp',
    maximumBytes: 25 * 1024 * 1024,
);
```

Oversized inputs throw `FileTooLargeException`, and partially created workspaces are rolled back.

`TemporaryFile::open()` provides a readable stream for downstream streaming APIs:

```php
$stream = $file->open();

try {
    // Pass $stream to an uploader or parser.
} finally {
    fclose($stream);
}
```

`read()`, `toBase64()`, and `toDataUri()` necessarily return complete strings and should be reserved for suitably sized files.

## Remote URL security

The default `PublicUrlPolicy` rejects localhost, literal private/reserved IP addresses, and hostnames whose current DNS answers include private/reserved addresses. HTTP redirects are disabled so an unchecked redirect target is never followed.

Remote URL ingestion still crosses a security boundary. DNS can change between validation and connection, so high-risk applications should resolve and download through infrastructure they control or provide a custom `SourceResolverInterface`.

For already trusted URLs, the public-address policy can be replaced explicitly:

```php
use OneToMany\DataUri\DataDecoder;
use OneToMany\DataUri\Source\AllowAnyUrlPolicy;
use OneToMany\DataUri\Source\SourceResolver;

$decoder = new DataDecoder(
    sourceResolver: new SourceResolver(
        new AllowAnyUrlPolicy(),
    ),
);
```

Remote requests use a ten-second timeout, do not follow redirects, require TLS peer verification for HTTPS, and remain subject to the configured decoded-size limit.

## Storage keys

Remote object-key policy is separate from temporary-file ownership:

```php
use OneToMany\DataUri\Storage\StorageKeyGenerator;

$generator = new StorageKeyGenerator('uploads');
$key = $generator->generate($file);

// uploads/18/5f/hello.txt
```

Keys are based on the SHA-256 content hash and safe caller-supplied filename. When no name was supplied, the hash itself becomes the filename. Keys are therefore deterministic and independent of both the random temporary path and generated temporary name.

## Filename handling

The caller-provided name is available through `getOriginalName()`. `getName()` returns the safe single-segment name used on disk.

Safe names:

- Preserve Unicode letters and numbers.
- Replace unsafe character runs with `_`.
- Reject reserved `"."` and `".."` segments.
- Are capped at 180 bytes before a known extension is appended.

Every file receives a unique workspace, so identical display names do not collide.

## Extending the pipeline

The main seams are public interfaces:

- `FileReferenceInterface`, `ReadableFileInterface`, `HashableFileInterface`, and `EncodableFileInterface` let consumers depend on only the file capabilities they use.
- `TemporaryFileInterface` aggregates those file capabilities with cleanup ownership.
- `SourceResolverInterface` classifies and opens source data.
- `TemporaryFileFactoryInterface` materializes bounded streams transactionally.
- `MediaTypeResolverInterface` controls MIME precedence and detection.
- `UrlPolicyInterface` controls remote URL acceptance.
- `StorageKeyGeneratorInterface` controls remote key policy.

`DataDecoder` accepts these collaborators through its constructor while providing safe defaults.

## Exceptions

All library exceptions implement `OneToMany\DataUri\Contract\Exception\ExceptionInterface`.

- `InvalidArgumentException`: invalid source, filename, media type, Base64, or URL policy violation.
- `FileTooLargeException`: decoded content exceeded the configured limit.
- `RuntimeException`: source, filesystem, DNS, or encoding operation failed.

## Legacy API

`OneToMany\DataUri\Record\DataUri` and `DataUriInterface` remain as deprecated migration shims for existing type references. New code should use `TemporaryFile` and `TemporaryFileInterface`. Key generation has moved to `StorageKeyGenerator`, and the old duplicate public-property contract is not part of the new interface. Legacy manual `DataUri` construction is intentionally unmanaged so an arbitrary constructor argument can never authorize recursive deletion.

## Example application

Run the console example:

```shell
php examples/decode.php
```

Pass `--all` to include a remote URL example.

## License

MIT
