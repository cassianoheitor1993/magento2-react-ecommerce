import TrustBadgesWidget from './TrustBadgesWidget/trustBadgesWidget';
import TestimonialsWidget from './TestimonialsWidget/testimonialsWidget';
import CtaWidget from './CtaWidget/ctaWidget';
import CategoriesCarouselWidget from './CategoriesCarouselWidget/categoriesCarouselWidget';
import NewsletterWidget from './NewsletterWidget/newsletterWidget';

const widgetRegistry = {
    trust_badges: TrustBadgesWidget,
    testimonials: TestimonialsWidget,
    cta: CtaWidget,
    categories_carousel: CategoriesCarouselWidget,
    newsletter: NewsletterWidget
};

export default widgetRegistry;
