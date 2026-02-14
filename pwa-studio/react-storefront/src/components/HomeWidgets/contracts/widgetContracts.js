/**
 * @typedef {{
 *   type: 'navigate' | 'modal';
 *   url?: string;
 *   target?: '_self' | '_blank';
 *   modalId?: string;
 * }} Action
 *
 * @typedef {{
 *   name: string;
 *   position: 'left' | 'right';
 * }} IconConfig
 *
 * @typedef {{
 *   eyebrow?: string;
 *   headline: string;
 *   subheadline?: string;
 *   body?: string;
 *   disclaimer?: string;
 * }} CTAContent
 *
 * @typedef {{
 *   label: string;
 *   secondaryLabel?: string;
 *   icon?: IconConfig;
 * }} CTAActions
 *
 * @typedef {{
 *   primaryAction: Action;
 *   secondaryAction?: Action;
 * }} CTABehavior
 *
 * @typedef {{
 *   id?: string;
 *   type: 'cta';
 *   version?: string;
 *   status?: 'active' | 'inactive';
 *   priority?: number;
 *   content: CTAContent;
 *   cta: CTAActions;
 *   behavior: CTABehavior;
 *   design?: Record<string, unknown>;
 *   animation?: Record<string, unknown>;
 *   analytics?: Record<string, unknown>;
 *   experiment?: Record<string, unknown>;
 *   conditions?: Record<string, unknown>;
 *   accessibility?: Record<string, unknown>;
 *   visibility?: { startAt?: string; endAt?: string };
 * }} CTAWidget
 */

export {};
