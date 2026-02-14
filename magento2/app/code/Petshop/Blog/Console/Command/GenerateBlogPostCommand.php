<?php
namespace Petshop\Blog\Console\Command;

use Petshop\Blog\Model\BlogPostManager;
use Petshop\Blog\Model\DeepSeekClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBlogPostCommand extends Command
{
    public function __construct(
        private readonly DeepSeekClient $deepSeekClient,
        private readonly BlogPostManager $blogPostManager
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('petshop:blog:generate')
            ->setDescription('Generate a blog post using DeepSeek API')
            ->addOption('topic', null, InputOption::VALUE_REQUIRED, 'Main topic')
            ->addOption('pet-type', null, InputOption::VALUE_REQUIRED, 'Pet type', 'dogs')
            ->addOption('tone', null, InputOption::VALUE_REQUIRED, 'Writing tone', 'helpful and professional')
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'Preferred title')
            ->addOption('save', null, InputOption::VALUE_NONE, 'Save generated post in database');

        return parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $topic = (string) $input->getOption('topic');
        if ($topic === '') {
            $output->writeln('<error>--topic is required.</error>');
            return self::FAILURE;
        }

        try {
            $postData = $this->deepSeekClient->generatePost(
                topic: $topic,
                petType: (string) $input->getOption('pet-type'),
                tone: (string) $input->getOption('tone'),
                title: $input->getOption('title') ? (string) $input->getOption('title') : null
            );

            if ($input->getOption('save')) {
                $newId = $this->blogPostManager->create($postData);
                $output->writeln('<info>Generated and saved post ID: ' . $newId . '</info>');
            } else {
                $output->writeln('<info>Generated post preview:</info>');
                $output->writeln('Title: ' . $postData['title']);
                $output->writeln('Excerpt: ' . $postData['excerpt']);
                $output->writeln('Tags: ' . $postData['tags']);
                $output->writeln('--- Content ---');
                $output->writeln($postData['content']);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }
    }
}
