<?php

use OneToMany\DataUri\Contract\Enum\FileType;

$mapper = static fn (FileType $ft): string => $ft->getName();

/** @var list<non-empty-string> $mappedNames */
$mappedNames = array_map($mapper, FileType::cases());

/** @var array<non-empty-string> $messages */
$messages = array_combine($mappedNames, $mappedNames);

return $messages;
