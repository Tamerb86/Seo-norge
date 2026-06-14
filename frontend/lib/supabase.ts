import { createClient } from '@supabase/supabase-js'
import { createClientComponentClient, createServerComponentClient } from '@supabase/auth-helpers-nextjs'
import { cookies } from 'next/headers'

// Types for our database
export type Database = {
  public: {
    Tables: {
      profiles: {
        Row: {
          id: string
          email: string
          full_name: string | null
          company: string | null
          plan: 'free' | 'starter' | 'professional' | 'agency'
          created_at: string
          updated_at: string
        }
        Insert: {
          id: string
          email: string
          full_name?: string | null
          company?: string | null
          plan?: 'free' | 'starter' | 'professional' | 'agency'
        }
        Update: {
          full_name?: string | null
          company?: string | null
          plan?: 'free' | 'starter' | 'professional' | 'agency'
        }
      }
      domains: {
        Row: {
          id: string
          user_id: string
          domain: string
          name: string | null
          created_at: string
          updated_at: string
        }
        Insert: {
          user_id: string
          domain: string
          name?: string | null
        }
        Update: {
          domain?: string
          name?: string | null
        }
      }
      keywords: {
        Row: {
          id: string
          domain_id: string
          keyword: string
          search_volume: number | null
          difficulty: number | null
          cpc: number | null
          created_at: string
        }
        Insert: {
          domain_id: string
          keyword: string
          search_volume?: number | null
          difficulty?: number | null
          cpc?: number | null
        }
        Update: {
          keyword?: string
          search_volume?: number | null
          difficulty?: number | null
          cpc?: number | null
        }
      }
      rankings: {
        Row: {
          id: string
          keyword_id: string
          position: number | null
          url: string | null
          checked_at: string
        }
        Insert: {
          keyword_id: string
          position?: number | null
          url?: string | null
          checked_at?: string
        }
        Update: {
          position?: number | null
          url?: string | null
        }
      }
    }
  }
}

// Environment variables
const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL!
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!

// Client-side Supabase client (for use in Client Components)
export const createBrowserClient = () => {
  return createClientComponentClient<Database>()
}

// Server-side Supabase client (for use in Server Components)
export const createServerClient = () => {
  const cookieStore = cookies()
  return createServerComponentClient<Database>({ cookies: () => cookieStore })
}

// Admin client (for server-side operations that need elevated privileges)
export const createAdminClient = () => {
  const supabaseServiceKey = process.env.SUPABASE_KEY!
  return createClient<Database>(supabaseUrl, supabaseServiceKey, {
    auth: {
      autoRefreshToken: false,
      persistSession: false,
    },
  })
}

// Helper function to get current user
export const getCurrentUser = async () => {
  const supabase = createServerClient()
  const { data: { user }, error } = await supabase.auth.getUser()
  
  if (error || !user) {
    return null
  }
  
  return user
}

// Helper function to get user profile
export const getUserProfile = async (userId: string) => {
  const supabase = createServerClient()
  const { data, error } = await supabase
    .from('profiles')
    .select('*')
    .eq('id', userId)
    .single()
  
  if (error) {
    console.error('Error fetching profile:', error)
    return null
  }
  
  return data
}
