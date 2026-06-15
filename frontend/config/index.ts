/**
 * Application Configuration
 * 
 * Centralized configuration for the SEO Norge application.
 * All environment variables and settings are managed here.
 */

// Environment validation
const getEnvVar = (key: string, defaultValue?: string): string => {
  const value = process.env[key] || defaultValue;
  if (!value && !defaultValue) {
    console.warn(`Missing environment variable: ${key}`);
  }
  return value || '';
};

// Application settings
export const config = {
  // App
  app: {
    name: 'SEO Norge',
    description: 'AI-Powered SEO Tool for Norwegian Market',
    url: getEnvVar('NEXT_PUBLIC_APP_URL', 'http://localhost:3000'),
    env: getEnvVar('NODE_ENV', 'development'),
    isDev: process.env.NODE_ENV === 'development',
    isProd: process.env.NODE_ENV === 'production',
  },

  // API
  api: {
    baseUrl: getEnvVar('NEXT_PUBLIC_API_URL', 'http://localhost:8000/api'),
    timeout: 30000,
  },

  // Supabase (public, browser-safe values only)
  supabase: {
    url: getEnvVar('NEXT_PUBLIC_SUPABASE_URL'),
    anonKey: getEnvVar('NEXT_PUBLIC_SUPABASE_ANON_KEY'),
  },

  // Stripe (publishable key only — secrets live on the Laravel backend)
  stripe: {
    publicKey: getEnvVar('NEXT_PUBLIC_STRIPE_PUBLIC_KEY'),
  },
  // NOTE: OpenAI / Supabase service-role / Stripe secret keys are intentionally
  // NOT referenced here. They are server-only and live exclusively on the
  // Laravel backend; keeping them out of this shared module prevents any
  // accidental import into a client bundle.

  // Feature flags
  features: {
    aiAnalysis: getEnvVar('FEATURE_AI_ANALYSIS', 'true') === 'true',
    competitorTracking: getEnvVar('FEATURE_COMPETITOR_TRACKING', 'true') === 'true',
    localSeo: getEnvVar('FEATURE_LOCAL_SEO', 'true') === 'true',
  },

  // Limits (based on plan)
  limits: {
    free: {
      domains: 1,
      keywords: 10,
      aiAnalyses: 5,
    },
    starter: {
      domains: 3,
      keywords: 50,
      aiAnalyses: 25,
    },
    professional: {
      domains: 10,
      keywords: 200,
      aiAnalyses: 100,
    },
    agency: {
      domains: 50,
      keywords: 1000,
      aiAnalyses: 500,
    },
  },

  // SEO settings
  seo: {
    defaultTitle: 'SEO Norge - AI-drevet SEO-verktøy for Norge',
    titleTemplate: '%s | SEO Norge',
    defaultDescription: 'Norges første AI-drevne SEO-verktøy. Spor rangeringer, finn søkeord, analyser konkurrenter og optimaliser innholdet ditt.',
    siteUrl: getEnvVar('NEXT_PUBLIC_APP_URL', 'https://seo-norge.no'),
    locale: 'nb_NO',
    twitterHandle: '@seonorge',
  },
} as const;

// Type exports
export type AppConfig = typeof config;
export type PlanType = keyof typeof config.limits;
