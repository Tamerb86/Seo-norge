import { createClient } from '@supabase/supabase-js'

// Database types
export type Database = {
  public: {
    Tables: {
      users: {
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
    }
  }
}

// NEXT_PUBLIC_* values are inlined at build time. Fallbacks keep the build from
// failing if env vars are missing; real values are required at runtime.
const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://placeholder.supabase.co'
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || 'placeholder-anon-key'

// Single browser client â€” safe to import in Client Components.
export const supabase = createClient<Database>(supabaseUrl, supabaseAnonKey, {
  auth: {
    persistSession: true,
    autoRefreshToken: true,
    detectSessionInUrl: true,
  },
})

// Back-compat factory used by hooks/use-auth.ts
export const createBrowserClient = () => supabase

// Admin client â€” ONLY use in server-side code where SUPABASE_KEY (service_role) is set.
export const createAdminClient = () => {
  const serviceKey = process.env.SUPABASE_KEY || ''
  return createClient<Database>(supabaseUrl, serviceKey, {
    auth: { autoRefreshToken: false, persistSession: false },
  })
}
