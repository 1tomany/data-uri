<?php

namespace OneToMany\DataUri
{

    use OneToMany\DataUri\Exception\GeneratingDataUriFailedException;

    if (!function_exists('OneToMany\DataUri\file_to_uri')) {
        function file_to_uri(string $mimeType, string $filepath): string
        {
            if (false === $bytes = @file_get_contents($filepath)) {
                throw new GeneratingDataUriFailedException($filepath);
            }

            return sprintf('data:%s;base64,%s', strtolower($mimeType), base64_encode($bytes));
        }
    }

}
