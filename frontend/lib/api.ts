/**
 * API Client
 * 
 * Centralized API client for the SEO Norge application.
 * Handles all HTTP requests with authentication and error handling.
 */

import axios, { AxiosError, AxiosInstance, AxiosRequestConfig } from 'axios';
import { createBrowserClient } from './supabase';
import { config } from '@/config';
import type {
  ApiResponse,
  ApiError,
  Domain,
  Keyword,
  Ranking,
  RankingOverview,
  KeywordSuggestion,
  ContentAnalysis,
  GeneratedContent,
  Competitor,
  CompetitorAnalysis,
  UserProfile,
  UsageStats,
  Plan,
  Invoice,
  CreateDomainInput,
  UpdateDomainInput,
  CreateKeywordInput,
  AnalyzeContentInput,
  SuggestKeywordsInput,
  GenerateContentInput,
} from '@/types';

// ============================================
// API Instance Setup
// ============================================

const api: AxiosInstance = axios.create({
  baseURL: config.api.baseUrl,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: config.api.timeout,
});

// Request interceptor - Add auth token
api.interceptors.request.use(
  async (requestConfig) => {
    const supabase = createBrowserClient();
    const {
      data: { session },
    } = await supabase.auth.getSession();

    if (session?.access_token) {
      requestConfig.headers.Authorization = `Bearer ${session.access_token}`;
    }

    return requestConfig;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Handle errors and token refresh
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiError>) => {
    const originalRequest = error.config as AxiosRequestConfig & { _retry?: boolean };

    // Handle 401 - Token expired
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      const supabase = createBrowserClient();
      const { error: refreshError } = await supabase.auth.refreshSession();

      if (refreshError) {
        // Refresh failed, redirect to login
        if (typeof window !== 'undefined') {
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }

      // Retry the original request
      return api.request(originalRequest);
    }

    return Promise.reject(error);
  }
);

// ============================================
// API Methods - Domains
// ============================================

export const domainsApi = {
  /**
   * Get all domains for the current user
   */
  list: () => api.get<ApiResponse<Domain[]>>('/domains'),

  /**
   * Get a single domain by ID
   */
  get: (id: string) => api.get<ApiResponse<Domain>>(`/domains/${id}`),

  /**
   * Create a new domain
   */
  create: (data: CreateDomainInput) => api.post<ApiResponse<Domain>>('/domains', data),

  /**
   * Update a domain
   */
  update: (id: string, data: UpdateDomainInput) =>
    api.put<ApiResponse<Domain>>(`/domains/${id}`, data),

  /**
   * Delete a domain
   */
  delete: (id: string) => api.delete(`/domains/${id}`),
};

// ============================================
// API Methods - Keywords
// ============================================

export const keywordsApi = {
  /**
   * Get all keywords for a domain
   */
  list: (domainId: string) =>
    api.get<ApiResponse<Keyword[]>>(`/domains/${domainId}/keywords`),

  /**
   * Add a single keyword to a domain
   */
  create: (domainId: string, data: CreateKeywordInput) =>
    api.post<ApiResponse<Keyword>>(`/domains/${domainId}/keywords`, data),

  /**
   * Add multiple keywords at once
   */
  bulkCreate: (domainId: string, keywords: string[]) =>
    api.post<ApiResponse<Keyword[]>>(`/domains/${domainId}/keywords/bulk`, { keywords }),

  /**
   * Delete a keyword
   */
  delete: (domainId: string, keywordId: string) =>
    api.delete(`/domains/${domainId}/keywords/${keywordId}`),

  /**
   * Search for keyword suggestions
   */
  search: (query: string) =>
    api.get<ApiResponse<KeywordSuggestion[]>>('/keywords/search', {
      params: { q: query },
    }),
};

// ============================================
// API Methods - Rankings
// ============================================

export const rankingsApi = {
  /**
   * Get ranking history for a keyword
   */
  history: (keywordId: string, days: number = 30) =>
    api.get<ApiResponse<Ranking[]>>(`/keywords/${keywordId}/rankings`, {
      params: { days },
    }),

  /**
   * Get latest rankings overview for a domain
   */
  latest: (domainId: string) =>
    api.get<ApiResponse<RankingOverview[]>>(`/domains/${domainId}/rankings/latest`),

  /**
   * Trigger a manual ranking refresh
   */
  refresh: (domainId: string) => api.post(`/domains/${domainId}/rankings/refresh`),
};

// ============================================
// API Methods - AI Analysis
// ============================================

export const aiApi = {
  /**
   * Analyze content for SEO optimization
   */
  analyzeContent: (data: AnalyzeContentInput) =>
    api.post<ApiResponse<ContentAnalysis>>('/ai/analyze-content', data),

  /**
   * Get keyword suggestions based on a seed keyword
   */
  suggestKeywords: (data: SuggestKeywordsInput) =>
    api.post<ApiResponse<KeywordSuggestion[]>>('/ai/suggest-keywords', {
      seed_keyword: data.seedKeyword,
      language: data.language,
      count: data.count,
    }),

  /**
   * Generate SEO-optimized content
   */
  generateContent: (data: GenerateContentInput) =>
    api.post<ApiResponse<GeneratedContent>>('/ai/generate-content', {
      keyword: data.keyword,
      content_type: data.contentType,
      language: data.language,
      tone: data.tone,
    }),
};

// ============================================
// API Methods - Competitors
// ============================================

export const competitorsApi = {
  /**
   * Analyze a competitor domain
   */
  analyze: (domainId: string, competitorDomain: string) =>
    api.post<ApiResponse<CompetitorAnalysis>>(`/domains/${domainId}/competitors/analyze`, {
      competitor_domain: competitorDomain,
    }),

  /**
   * Get all tracked competitors for a domain
   */
  list: (domainId: string) =>
    api.get<ApiResponse<Competitor[]>>(`/domains/${domainId}/competitors`),

  /**
   * Add a competitor to track
   */
  add: (domainId: string, competitorDomain: string) =>
    api.post<ApiResponse<Competitor>>(`/domains/${domainId}/competitors`, {
      competitor_domain: competitorDomain,
    }),

  /**
   * Remove a competitor
   */
  remove: (domainId: string, competitorId: string) =>
    api.delete(`/domains/${domainId}/competitors/${competitorId}`),
};

// ============================================
// API Methods - User
// ============================================

export const userApi = {
  /**
   * Get current user profile
   */
  profile: () => api.get<ApiResponse<UserProfile>>('/user/profile'),

  /**
   * Update user profile
   */
  updateProfile: (data: Partial<UserProfile>) =>
    api.put<ApiResponse<UserProfile>>('/user/profile', data),

  /**
   * Get usage statistics
   */
  usage: () => api.get<ApiResponse<UsageStats>>('/user/usage'),

  /**
   * Get dashboard data
   */
  dashboard: () => api.get('/user/dashboard'),
};

// ============================================
// API Methods - Billing
// ============================================

export const billingApi = {
  /**
   * Get available plans
   */
  plans: () => api.get<ApiResponse<Plan[]>>('/billing/plans'),

  /**
   * Create a subscription checkout session
   */
  subscribe: (planId: string) =>
    api.post<ApiResponse<{ checkout_url: string }>>('/billing/subscribe', {
      plan_id: planId,
    }),

  /**
   * Get customer portal URL
   */
  portal: () => api.post<ApiResponse<{ portal_url: string }>>('/billing/portal'),

  /**
   * Get invoice history
   */
  invoices: () => api.get<ApiResponse<Invoice[]>>('/billing/invoices'),

  /**
   * Cancel subscription
   */
  cancel: () => api.post('/billing/cancel'),
};

// ============================================
// API Methods - Onboarding
// ============================================

export const onboardingApi = {
  /**
   * Get onboarding status
   */
  status: () => api.get<ApiResponse<{ completed: boolean; step: number }>>('/onboarding/status'),

  /**
   * Update onboarding progress
   */
  update: (step: number, data: Record<string, unknown>) =>
    api.post('/onboarding/progress', { step, data }),

  /**
   * Complete onboarding
   */
  complete: () => api.post('/onboarding/complete'),

  /**
   * Get personalized tips
   */
  tips: () => api.get<ApiResponse<string[]>>('/onboarding/tips'),
};

// Export the API instance for custom requests
export { api };
export default api;
