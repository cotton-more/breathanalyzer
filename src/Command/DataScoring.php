<?php namespace Breathanalyzer\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Stopwatch\Stopwatch;

class DataScoring extends Command
{
    private $vocabulary;
    private $vocabularyByLength;

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
        $words = preg_split('/\s+/', trim(file_get_contents($filename)));

        $stopwatch = new Stopwatch;
        $stopwatch->start('getScore');

        $totalScore = 0;
        foreach ($words as $word) {
            $totalScore += $this->getWordScore($word);
        }

        $event = $stopwatch->stop('getScore');

        $output->writeln('<info>'.$totalScore.'</info>');
        $output->writeln(sprintf('<comment>Time: %0.2fs.</comment>', $event->getDuration()/1000));
    }

    private function suggestWordLengths($word)
    {
        $wordLength = mb_strlen($word);

        $lenghts = array();
        foreach (array_keys($this->vocabularyByLength) as $l) {
            $lenghts[ $l ] = abs($l - $wordLength);
        }

        asort($lenghts);

        return $lenghts;
    }

    private function getWordScore($word)
    {
        $word = mb_strtoupper($word);
        if (isset($this->words[$word])) {
            return $this->words[$word];
        }

        if (isset($this->vocabulary[$word])) {
            return $this->words[$word] = 0;
        }

        $score = null;

        $lengths = $this->suggestWordLengths($word);
        foreach ($lengths as $vocabularyLength => $_) {
            $minimumScore = abs($vocabularyLength - mb_strlen($word));
            if (0 === $minimumScore) {
                $minimumScore = 1;
            }
            foreach ($this->vocabularyByLength[$vocabularyLength] as $vocabulary) {
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
            if ($score === $minimumScore) {
                break;
            }
        }

        return $this->words[$word] = $score;
    }

    private function buildVocabulary()
    {
        $this->vocabulary = array_fill_keys(file(realpath(__DIR__.'/../../data/vocabulary.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), true);

        foreach (array_keys($this->vocabulary) as $word) {
            $this->vocabularyByLength[mb_strlen($word)][] = $word;
        }
    }
}
