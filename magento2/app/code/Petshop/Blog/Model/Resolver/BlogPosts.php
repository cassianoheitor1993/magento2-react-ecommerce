<?php
namespace Petshop\Blog\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Petshop\Blog\Model\ResourceModel\Post\CollectionFactory;

class BlogPosts implements ResolverInterface
{
    protected CollectionFactory $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('is_published', 1)
            ->setOrder('created_at', 'DESC');
        $posts = [];

        foreach ($collection as $post) {
            $slug = (string) $post->getData('slug');
            if (trim($slug) === '') {
                $slug = $this->buildFallbackSlug(
                    (string) $post->getTitle(),
                    (int) $post->getId()
                );
            }

            $posts[] = [
                'post_id' => $post->getId(),
                'title' => $post->getTitle(),
                'slug' => $slug,
                'excerpt' => $post->getData('excerpt'),
                'content' => $post->getContent(),
                'author' => $post->getData('author'),
                'tags' => $post->getData('tags'),
                'is_published' => (int) $post->getData('is_published'),
                'created_at' => $post->getCreatedAt(),
                'updated_at' => $post->getData('updated_at')
            ];
        }

        return ['items' => $posts];
    }

    private function buildFallbackSlug(string $title, int $postId): string
    {
        $base = strtolower(trim($title));
        $base = preg_replace('/[^a-z0-9]+/i', '-', $base);
        $base = trim((string) $base, '-');

        if ($base === '') {
            $base = 'blog-post';
        }

        return sprintf('%s-%d', $base, $postId);
    }
}
