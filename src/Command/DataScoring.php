<?php namespace Breathanalyzer\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\ProgressBar;

class DataScoring extends Command
{
    private $vocabulary;

    private $words = array();

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

        $filename = $input->getArgument('filename');
        $words = preg_split('/\s+/', file_get_contents($filename));

        $progress = new ProgressBar($output, count($words));
        $progress->setFormat('very_verbose');
        $progress->start();

        $totalScore = 0;
        foreach ($words as $word) {
            $totalScore += $this->getWordScore($word);
            $progress->advance();
        }

        $progress->finish();

        $output->writeln('');
        $output->writeln('<info>'.$totalScore.'</info>');
    }

    private function getWordScore($word)
    {
        $word = mb_strtoupper($word);
        if (array_key_exists($word, $this->words)) {
            return $this->words[$word];
        }

        if (in_array($word, $this->vocabulary)) {
            return $this->words[$word] = 0;
        }

        $minimumScore = 1;
        $score = null;

        foreach ($this->vocabulary as $vocabulary) {
            $wordScore = levenshtein($word, $vocabulary);
            if ($wordScore === $minimumScore) {
                $score = $wordScore;
                break;
            } else {
                if ($wordScore < $score || null === $score) {
                    $score = $wordScore;
                }
            }
        }

        return $this->words[$word] = $score;
    }

    private function buildVocabulary()
    {
        $this->vocabulary = file(realpath(__DIR__.'/../../data/vocabulary.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
}
