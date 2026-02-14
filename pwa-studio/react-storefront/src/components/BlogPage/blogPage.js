import React, { useMemo, useState } from 'react';
import { useQuery } from '@apollo/client';
import { Link, useLocation } from 'react-router-dom';
import DOMPurify from 'dompurify';
import { GET_BLOG_POSTS } from '../../queries/getBlogPosts.gql';
import { mergeClasses } from '@magento/venia-ui/lib/classify';
import { fullPageLoadingIndicator } from '@magento/venia-ui/lib/components/LoadingIndicator';
import ErrorView from '@magento/venia-ui/lib/components/ErrorView';
import resourceUrl from '@magento/peregrine/lib/util/makeUrl';
import defaultClasses from './blogPage.module.css';

const PAGE_SIZE = 6;

const sanitizeHtml = html =>
    DOMPurify.sanitize(html || '', {
        ADD_ATTR: ['target', 'rel', 'style'],
        ADD_TAGS: ['img']
    });

const resolveMediaDirectives = html => {
    if (!html) {
        return '';
    }

    return String(html).replace(
        /\{\{\s*media\s+url=(?:"|&quot;)([^"}]+?)(?:"|&quot;)\s*\}\}/gi,
        (match, rawPath) => {
            const cleaned = String(rawPath || '')
                .trim()
                .replace(/^\.\//, '')
                .replace(/^\/+/, '');

            if (!cleaned) {
                return match;
            }

            return resourceUrl(`/media/${cleaned}`);
        }
    );
};

const sanitizeCmsHtml = html => sanitizeHtml(resolveMediaDirectives(html));

const normalizeTagList = tags =>
    String(tags || '')
        .split(',')
        .map(tag => tag.trim())
        .filter(Boolean);

const formatDate = value =>
    value ? new Date(value).toLocaleDateString() : '';

const BlogPage = props => {
    const classes = mergeClasses(defaultClasses, props.classes);
    const location = useLocation();
    const routeSlug = props?.match?.params?.slug || '';
    const pathSlug = useMemo(() => {
        const path = location?.pathname || '';
        const matched = path.match(/\/blog\/([^/?#]+)/i);
        return matched && matched[1] ? decodeURIComponent(matched[1]) : '';
    }, [location?.pathname]);
    const slug = routeSlug ? decodeURIComponent(routeSlug) : pathSlug;
    const { loading, error, data } = useQuery(GET_BLOG_POSTS, {
        fetchPolicy: 'cache-and-network',
        nextFetchPolicy: 'cache-first',
        notifyOnNetworkStatusChange: true
    });
    const [query, setQuery] = useState('');
    const [activeTopic, setActiveTopic] = useState('all');
    const [currentPage, setCurrentPage] = useState(1);

    const blogPosts = data?.blogPosts?.items || [];

    const topics = useMemo(() => {
        const set = new Set();
        blogPosts.forEach(post => {
            normalizeTagList(post.tags).forEach(tag => set.add(tag.toLowerCase()));
        });

        return ['all', ...Array.from(set).sort((a, b) => a.localeCompare(b))];
    }, [blogPosts]);

    const filteredPosts = useMemo(() => {
        const q = query.trim().toLowerCase();

        return blogPosts.filter(post => {
            const textIndex = [
                post.title,
                post.author,
                post.excerpt,
                post.content,
                post.tags
            ]
                .join(' ')
                .toLowerCase();

            const matchesQuery = q === '' || textIndex.includes(q);
            const tags = normalizeTagList(post.tags).map(tag => tag.toLowerCase());
            const matchesTopic = activeTopic === 'all' || tags.includes(activeTopic);

            return matchesQuery && matchesTopic;
        });
    }, [blogPosts, query, activeTopic]);

    const totalPages = Math.max(1, Math.ceil(filteredPosts.length / PAGE_SIZE));
    const safePage = Math.min(currentPage, totalPages);
    const paginatedPosts = filteredPosts.slice(
        (safePage - 1) * PAGE_SIZE,
        safePage * PAGE_SIZE
    );

    const post = slug
        ? blogPosts.find(item => String(item.slug || '') === String(slug))
        : null;

    if (loading) return fullPageLoadingIndicator;
    if (error) return <ErrorView message={error.message} />;

    const renderSidebar = () => (
        <aside className={classes.sidebar}>
            <div className={classes.sidebarCard}>
                <h3 className={classes.sidebarTitle}>Buscar posts</h3>
                <input
                    className={classes.searchInput}
                    type="search"
                    placeholder="Procure por título, conteúdo, autor..."
                    value={query}
                    onChange={event => {
                        setQuery(event.target.value);
                        setCurrentPage(1);
                    }}
                />
            </div>

            <div className={classes.sidebarCard}>
                <h3 className={classes.sidebarTitle}>Tópicos</h3>
                <ul className={classes.topicList}>
                    {topics.map(topic => (
                        <li key={topic}>
                            <button
                                type="button"
                                className={`${classes.topicButton} ${
                                    activeTopic === topic ? classes.topicButtonActive : ''
                                }`}
                                onClick={() => {
                                    setActiveTopic(topic);
                                    setCurrentPage(1);
                                }}
                            >
                                {topic === 'all' ? 'Todos' : `#${topic}`}
                            </button>
                        </li>
                    ))}
                </ul>
            </div>
        </aside>
    );

    const renderPagination = () => {
        if (totalPages <= 1) {
            return null;
        }

        const pages = Array.from({ length: totalPages }, (_, index) => index + 1);
        return (
            <nav className={classes.pagination} aria-label="Blog pagination">
                <button
                    type="button"
                    className={classes.pageButton}
                    onClick={() => setCurrentPage(page => Math.max(1, page - 1))}
                    disabled={safePage === 1}
                >
                    Anterior
                </button>

                {pages.map(page => (
                    <button
                        key={page}
                        type="button"
                        className={`${classes.pageButton} ${
                            safePage === page ? classes.pageButtonActive : ''
                        }`}
                        onClick={() => setCurrentPage(page)}
                    >
                        {page}
                    </button>
                ))}

                <button
                    type="button"
                    className={classes.pageButton}
                    onClick={() => setCurrentPage(page => Math.min(totalPages, page + 1))}
                    disabled={safePage === totalPages}
                >
                    Próxima
                </button>
            </nav>
        );
    };

    if (slug && !post) {
        return (
            <div className={classes.root}>
                <ErrorView message="Post não encontrado." />
                <div className={classes.backRow}>
                    <Link className={classes.backLink} to={resourceUrl('/blog')}>
                        ← Voltar para o blog
                    </Link>
                </div>
            </div>
        );
    }

    if (post) {
        return (
            <div className={classes.root}>
                <div className={classes.layout}>
                    {renderSidebar()}
                    <article className={classes.postDetail}>
                        <div className={classes.backRow}>
                            <Link className={classes.backLink} to={resourceUrl('/blog')}>
                                ← Voltar para o blog
                            </Link>
                        </div>
                        <h1 className={classes.detailTitle}>{post.title}</h1>
                        <p className={classes.postMeta}>
                            {post.author || 'LeisPet Team'} {' • '} {formatDate(post.created_at)}
                        </p>
                        {post.excerpt ? (
                            <div
                                className={classes.postExcerpt}
                                dangerouslySetInnerHTML={{
                                    __html: sanitizeCmsHtml(post.excerpt)
                                }}
                            />
                        ) : null}
                        <div
                            className={classes.postRichContent}
                            dangerouslySetInnerHTML={{
                                __html: sanitizeCmsHtml(post.content)
                            }}
                        />
                    </article>
                </div>
            </div>
        );
    }

    return (
        <div className={classes.root}>
            <h1 className={classes.title}>LeisPet Blog</h1>
            <p className={classes.subtitle}>Dicas, guias e novidades para quem ama pets.</p>

            <div className={classes.layout}>
                {renderSidebar()}

                <section className={classes.postsArea}>
                    <div className={classes.postsGrid}>
                        {paginatedPosts.map(postItem => (
                            <article key={postItem.post_id} className={classes.postCard}>
                                <h2 className={classes.postTitle}>
                                    <Link className={classes.postTitleLink} to={resourceUrl(`/blog/${postItem.slug}`)}>
                                        {postItem.title}
                                    </Link>
                                </h2>
                                <p className={classes.postMeta}>
                                    {postItem.author || 'LeisPet Team'} {' • '}
                                    {formatDate(postItem.created_at)}
                                </p>
                                {postItem.excerpt ? (
                                    <div
                                        className={classes.postExcerpt}
                                        dangerouslySetInnerHTML={{
                                            __html: sanitizeCmsHtml(postItem.excerpt)
                                        }}
                                    />
                                ) : null}
                                {postItem.tags ? (
                                    <p className={classes.postTags}>
                                        {normalizeTagList(postItem.tags)
                                            .map(tag => `#${tag}`)
                                            .join(' ')}
                                    </p>
                                ) : null}
                                <Link className={classes.readMore} to={resourceUrl(`/blog/${postItem.slug}`)}>
                                    Ler artigo completo →
                                </Link>
                            </article>
                        ))}
                    </div>
                    {renderPagination()}
                </section>
            </div>
        </div>
    );
};

export default BlogPage;
