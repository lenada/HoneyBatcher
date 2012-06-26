<?php

/*
 * This file is part of the HoneyBatcher utility.
 *
 * (c) Leander Damme <leander@wesrc.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use HoneyBatcher\HoneyBatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$console = new Application('HoneyBatcher', HoneyBatcher::VERSION);
$app = array();

$console
    ->register('batch')
    ->setDefinition(array(
    new InputArgument('source', InputArgument::REQUIRED, 'source path'),
    new InputArgument('destination', InputArgument::REQUIRED, 'destination path'),
    new InputArgument('batch-size', InputArgument::OPTIONAL, 'size of batches'),
    new InputOption('already-imported', '', InputOption::VALUE_REQUIRED, 'path to already imported files'),
))
    ->setDescription('Move batches of files to directories')
    ->setHelp(<<<EOF
Help here
EOF
)
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app)
{

    if ($input->getArgument('source')) {
        $app['data.source'] = $input->getArgument('source');
    }

    if ($input->getArgument('destination')) {
        $app['data.target'] = $input->getArgument('destination');
    }

    if ($input->getOption('already-imported')) {
        $app['data.imported'] = $input->getOption('already-imported');
    }


    $start = time();
    $startedOut = false;
    $startedErr = false;
    $callback = null;
    if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
        $callback = function ($type, $buffer) use ($output, &$startedOut, &$startedErr)
        {
            if ('err' === $type) {
                if (!$startedErr) {
                    $output->write("\nERR| ");
                    $startedErr = true;
                    $startedOut = false;
                }

                $output->write(str_replace("\n", "\nERR| ", $buffer));
            } else {
                if (!$startedOut) {
                    $output->write("\nOUT| ");
                    $startedOut = true;
                    $startedErr = false;
                }

                $output->write(str_replace("\n", "\nOUT| ", $buffer));
            }
        };
    }


    try {
        $output->writeln(sprintf('<info>Batching contents of "%s" (into "%s")</info>', $app['data.source'], $app['data.target']));
        $app['batcher'] = new HoneyBatcher($app['data.source'], $app['data.target'], $app['data.imported']);

        $output->writeln('');
    } catch (BuildException $e) {
        $output->writeln("\n" . sprintf('<error>%s</error>', $e->getMessage()));

        return 1;
    }

});

return $console;
