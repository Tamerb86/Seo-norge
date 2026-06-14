/**
 * Type Definitions
 * 
 * Centralized TypeScript types for the SEO Norge application.
 * All shared types are defined here for consistency.
 */

// ============================================
// User & Authentication
// ============================================

export type PlanType = 'free' | 'starter' | 'professional' | 'agency';

export interface User {
  id: string;
  email: string;
  fullName: string | null;
  company: string | null;
  plan: PlanType;
  createdAt: string;
  updatedAt: string;
}

export interface UserProfile extends User {
  avatarUrl?: string;
  onboardingCompleted: boolean;
  preferences: UserPreferences;
}

export interface UserPreferences {
  language: 'nb' | 'nn' | 'en';
  emailNotifications: boolean;
  weeklyReports: boolean;
}

// ============================================
// Domain & Keywords
// ============================================

export interface Domain {
  id: string;
  userId: string;
  domain: string;
  name: string | null;
  keywordsCount: number;
  createdAt: string;
  updatedAt: string;
}

export interface Keyword {
  id: string;
  domainId: string;
  keyword: string;
  searchVolume: number | null;
  difficulty: number | null;
  cpc: number | null;
  latestRanking: Ranking | null;
  createdAt: string;
}

export interface KeywordSuggestion {
  keyword: string;
  searchVolume: number;
  difficulty: number;
  cpc: number;
  trend: 'up' | 'down' | 'stable';
  intent?: SearchIntent;
}

export type SearchIntent = 'informational' | 'navigational' | 'transactional' | 'commercial';

// ============================================
// Rankings
// ============================================

export interface Ranking {
  id: string;
  keywordId: string;
  position: number | null;
  url: string | null;
  checkedAt: string;
  change: number;
}

export interface RankingOverview {
  keyword: Keyword;
  currentPosition: number | null;
  previousPosition: number | null;
  change: number;
  url: string | null;
}

export interface RankingHistory {
  date: string;
  position: number | null;
}

// ============================================
// Competitors
// ============================================

export interface Competitor {
  id: string;
  domainId: string;
  domain: string;
  commonKeywords: number;
  domainAuthority: number | null;
}

export interface CompetitorAnalysis {
  domain: string;
  commonKeywords: KeywordSuggestion[];
  uniqueKeywords: KeywordSuggestion[];
  contentGaps: string[];
  backlinkOpportunities: string[];
}

// ============================================
// AI Analysis
// ============================================

export interface ContentAnalysis {
  score: number;
  titleAnalysis: {
    score: number;
    current: string;
    suggestions: string[];
  };
  metaDescription: {
    score: number;
    current: string;
    suggestions: string[];
  };
  contentAnalysis: {
    wordCount: number;
    keywordDensity: number;
    readabilityScore: number;
    suggestions: string[];
  };
  technicalSeo: {
    headings: HeadingInfo[];
    imagesWithoutAlt: number;
    internalLinks: number;
    externalLinks: number;
  };
  overallSuggestions: string[];
}

export interface HeadingInfo {
  tag: 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6';
  text: string;
}

export interface GeneratedContent {
  content: string;
  wordCount: number;
  estimatedReadingTime: number;
}

export type ContentType = 'blog_post' | 'product_description' | 'meta_description' | 'title';
export type ContentTone = 'professional' | 'casual' | 'friendly';
export type Language = 'nb' | 'nn' | 'en';

// ============================================
// Billing & Plans
// ============================================

export interface Plan {
  id: string;
  name: string;
  price: number;
  currency: string;
  interval: 'month' | 'year';
  features: string[];
  limits: PlanLimits;
  popular?: boolean;
}

export interface PlanLimits {
  domains: number;
  keywords: number;
  aiAnalyses: number;
}

export interface Invoice {
  id: string;
  amount: number;
  currency: string;
  status: 'paid' | 'pending' | 'failed';
  createdAt: string;
  pdfUrl: string;
}

export interface UsageStats {
  domains: UsageItem;
  keywords: UsageItem;
  aiAnalyses: UsageItem;
  periodStart: string;
  periodEnd: string;
}

export interface UsageItem {
  used: number;
  limit: number;
}

// ============================================
// API Response Types
// ============================================

export interface ApiResponse<T> {
  data: T;
  message?: string;
  meta?: PaginationMeta;
}

export interface PaginationMeta {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  code?: string;
}

// ============================================
// Dashboard Types
// ============================================

export interface DashboardStats {
  totalDomains: number;
  totalKeywords: number;
  keywordsInTop10: number;
  keywordsInTop3: number;
  averagePosition: number | null;
}

export interface RankingChange {
  domain: string;
  keyword: string;
  change: number;
  currentPosition: number;
}

export interface DashboardData {
  stats: DashboardStats;
  recentChanges: RankingChange[];
  domains: Domain[];
}

// ============================================
// Form Types
// ============================================

export interface CreateDomainInput {
  domain: string;
  name?: string;
}

export interface UpdateDomainInput {
  name?: string;
}

export interface CreateKeywordInput {
  keyword: string;
}

export interface AnalyzeContentInput {
  url?: string;
  content?: string;
  keyword: string;
}

export interface SuggestKeywordsInput {
  seedKeyword: string;
  language?: Language;
  count?: number;
}

export interface GenerateContentInput {
  keyword: string;
  contentType: ContentType;
  language?: Language;
  tone?: ContentTone;
}

// ============================================
// Component Props Types
// ============================================

export interface BaseComponentProps {
  className?: string;
  children?: React.ReactNode;
}

export interface LoadingState {
  isLoading: boolean;
  error: string | null;
}
