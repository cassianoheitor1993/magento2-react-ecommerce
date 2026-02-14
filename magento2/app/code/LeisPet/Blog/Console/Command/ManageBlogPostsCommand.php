<?php
namespace LeisPet\Blog\Console\Command;

use LeisPet\Blog\Model\BlogPostManager;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ManageBlogPostsCommand extends Command
{
    public function __construct(private readonly BlogPostManager $blogPostManager)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('leispet:blog:manage')
            ->setDescription('Manage LeisPet blog posts (list/create/update/delete)')
            ->addArgument('action', InputArgument::REQUIRED, 'Action: list|create|update|delete')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Post ID for update/delete')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Post title')
            ->addOption('excerpt', null, InputOption::VALUE_REQUIRED, 'Post excerpt')
            ->addOption('content', null, InputOption::VALUE_REQUIRED, 'Post content (HTML allowed)')
            ->addOption('author', null, InputOption::VALUE_REQUIRED, 'Post author')
            ->addOption('tags', null, InputOption::VALUE_REQUIRED, 'Comma-separated tags')
            ->addOption('published', null, InputOption::VALUE_REQUIRED, '1 or 0', '1');

        return parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower((string) $input->getArgument('action'));

        try {
            return match ($action) {
                'list' => $this->handleList($output),
                'create' => $this->handleCreate($input, $output),
                'update' => $this->handleUpdate($input, $output),
                'delete' => $this->handleDelete($input, $output),
                default => throw new LocalizedException(__('Unknown action: %1', $action))
            };
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }
    }

    private function handleList(OutputInterface $output): int
    {
        $posts = $this->blogPostManager->list();
        foreach ($posts as $post) {
            $output->writeln(sprintf(
                '[%d] %s | slug=%s | author=%s | published=%d | %s',
                $post['post_id'],
                $post['title'],
                $post['slug'],
                $post['author'],
                $post['is_published'],
                $post['created_at']
            ));
        }

        return self::SUCCESS;
    }

    private function handleCreate(InputInterface $input, OutputInterface $output): int
    {
        $id = $this->blogPostManager->create($this->extractData($input));
        $output->writeln('<info>Created post ID: ' . $id . '</info>');

        return self::SUCCESS;
    }

    private function handleUpdate(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getOption('id');
        if ($id <= 0) {
            throw new LocalizedException(__('Option --id is required for update.'));
        }

        $this->blogPostManager->update($id, $this->extractData($input));
        $output->writeln('<info>Updated post ID: ' . $id . '</info>');

        return self::SUCCESS;
    }

    private function handleDelete(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getOption('id');
        if ($id <= 0) {
            throw new LocalizedException(__('Option --id is required for delete.'));
        }

        $this->blogPostManager->delete($id);
        $output->writeln('<info>Deleted post ID: ' . $id . '</info>');

        return self::SUCCESS;
    }

    private function extractData(InputInterface $input): array
    {
        return [
            'title' => (string) $input->getOption('title'),
            'excerpt' => (string) $input->getOption('excerpt'),
            'content' => (string) $input->getOption('content'),
            'author' => (string) $input->getOption('author'),
            'tags' => (string) $input->getOption('tags'),
            'is_published' => (int) $input->getOption('published')
        ];
    }
}
