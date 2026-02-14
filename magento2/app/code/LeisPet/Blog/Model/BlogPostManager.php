<?php
namespace LeisPet\Blog\Model;

use LeisPet\Blog\Model\PostFactory;
use LeisPet\Blog\Model\ResourceModel\Post as PostResource;
use LeisPet\Blog\Model\ResourceModel\Post\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class BlogPostManager
{
    public function __construct(
        private readonly PostFactory $postFactory,
        private readonly PostResource $postResource,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function create(array $data): int
    {
        $post = $this->postFactory->create();
        $post->setData($this->normalizeData($data));
        $this->postResource->save($post);

        return (int) $post->getId();
    }

    public function update(int $postId, array $data): void
    {
        $post = $this->postFactory->create();
        $this->postResource->load($post, $postId);
        if (!$post->getId()) {
            throw new NoSuchEntityException(__('Post with ID %1 does not exist.', $postId));
        }

        $post->addData($this->normalizeData($data));
        $this->postResource->save($post);
    }

    public function delete(int $postId): void
    {
        $post = $this->postFactory->create();
        $this->postResource->load($post, $postId);
        if (!$post->getId()) {
            throw new NoSuchEntityException(__('Post with ID %1 does not exist.', $postId));
        }

        $this->postResource->delete($post);
    }

    public function list(int $limit = 50): array
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder('created_at', 'DESC')->setPageSize($limit);

        $items = [];
        foreach ($collection as $post) {
            $items[] = [
                'post_id' => (int) $post->getId(),
                'title' => (string) $post->getData('title'),
                'slug' => (string) $post->getData('slug'),
                'author' => (string) $post->getData('author'),
                'is_published' => (int) $post->getData('is_published'),
                'created_at' => (string) $post->getData('created_at')
            ];
        }

        return $items;
    }

    private function normalizeData(array $data): array
    {
        $title = (string) ($data['title'] ?? 'Untitled Post');
        $slug = (string) ($data['slug'] ?? $this->buildSlug($title));

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => (string) ($data['excerpt'] ?? ''),
            'content' => (string) ($data['content'] ?? ''),
            'author' => (string) ($data['author'] ?? 'LeisPet Team'),
            'tags' => (string) ($data['tags'] ?? ''),
            'is_published' => isset($data['is_published']) ? (int) $data['is_published'] : 1
        ];
    }

    private function buildSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim((string) $slug, '-');

        if ($slug === '') {
            $slug = 'blog-post';
        }

        return $slug;
    }
}
