<?php

namespace OneToMany\DataUri\Command;

use OneToMany\DataUri\DataDecoder;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

final class EncodeFileCommand extends Command
{
    public function __construct(
        private readonly DataDecoder $dataDecoder = new DataDecoder(),
    ) {
        parent::__construct();
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument('Path to the file to encode')] string $path,
    ): int {
        $output->write($this->dataDecoder->decode($path)->toDataUri(), false, OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }

    /**
     * @see Symfony\Component\Console\Command\Command
     */
    #[\Override]
    protected function configure(): void
    {
        $this
            ->setName('onetomany:data-uri:encode-file')
            ->setDescription('Outputs a file as a base64 encoded data URL');
    }
}
