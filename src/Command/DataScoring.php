<?php namespace Breathanalyzer\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DataScoring extends Command
{
    private $vocabulary;

    protected function configure()
    {
        $this->setName('breathanalyzer:data-scoring');
        $this->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'File to analyze'
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        if (!$input->getArgument('filename')) {
            $question = new Question('<question>Input filename:</question> ');

            $filename = $helper->ask($input, $output, $question);

            $input->setArgument('filename', $filename);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->buildVocabulary();

        var_dump($this->vocabulary);

        $filename = $input->getArgument('filename');
        $srcText = file_get_contents($filename);
    }

    private function buildVocabulary()
    {
        $this->vocabulary = file(realpath(__DIR__.'/../../data/vocabulary.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
}
