'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { createBrowserClient } from '@/lib/supabase';
import { userApi } from '@/lib/api';
import type { User, AuthChangeEvent, Session } from '@supabase/supabase-js';
import type { UserProfile } from '@/types';

interface AuthState {
  user: User | null;
  profile: UserProfile | null;
  isLoading: boolean;
  isAuthenticated: boolean;
}

interface AuthActions {
  signIn: (email: string, password: string) => Promise<{ error: Error | null }>;
  signUp: (email: string, password: string, fullName?: string) => Promise<{ error: Error | null }>;
  signOut: () => Promise<void>;
  resetPassword: (email: string) => Promise<{ error: Error | null }>;
  updateProfile: (data: Partial<UserProfile>) => Promise<{ error: Error | null }>;
}

type UseAuthReturn = AuthState & AuthActions;

/**
 * Authentication hook for managing user sessions
 */
export function useAuth(): UseAuthReturn {
  const router = useRouter();
  const supabase: any = createBrowserClient();
  
  const [state, setState] = useState<AuthState>({
    user: null,
    profile: null,
    isLoading: true,
    isAuthenticated: false,
  });

  // Fetch user profile via the Laravel API. (The app tables have RLS enabled
  // with no policies, so the anon Supabase key CANNOT read them directly —
  // the backend is the only source of profile data.)
  const fetchProfile = useCallback(async (): Promise<UserProfile | null> => {
    try {
      const res = await userApi.profile();
      return (res.data?.data ?? null) as UserProfile | null;
    } catch (error) {
      console.error('Error fetching profile:', error);
      return null;
    }
  }, []);

  // Initialize auth state
  useEffect(() => {
    const initAuth = async () => {
      try {
        const { data: { session } } = await supabase.auth.getSession();
        
        if (session?.user) {
          const profile = await fetchProfile();
          setState({
            user: session.user,
            profile,
            isLoading: false,
            isAuthenticated: true,
          });
        } else {
          setState({
            user: null,
            profile: null,
            isLoading: false,
            isAuthenticated: false,
          });
        }
      } catch (error) {
        console.error('Auth initialization error:', error);
        setState(prev => ({ ...prev, isLoading: false }));
      }
    };

    initAuth();

    // Listen for auth changes
    const { data: { subscription } } = supabase.auth.onAuthStateChange(
      async (event: AuthChangeEvent, session: Session | null) => {
        if (event === 'SIGNED_IN' && session?.user) {
          const profile = await fetchProfile();
          setState({
            user: session.user,
            profile,
            isLoading: false,
            isAuthenticated: true,
          });
        } else if (event === 'SIGNED_OUT') {
          setState({
            user: null,
            profile: null,
            isLoading: false,
            isAuthenticated: false,
          });
        }
      }
    );

    return () => {
      subscription.unsubscribe();
    };
  }, [supabase, fetchProfile]);

  // Sign in with email and password
  const signIn = useCallback(async (email: string, password: string) => {
    const { error } = await supabase.auth.signInWithPassword({
      email,
      password,
    });

    if (!error) {
      router.push('/dashboard');
    }

    return { error };
  }, [supabase, router]);

  // Sign up with email and password
  const signUp = useCallback(async (email: string, password: string, fullName?: string) => {
    const { error } = await supabase.auth.signUp({
      email,
      password,
      options: {
        data: {
          full_name: fullName,
        },
      },
    });

    // NOTE: the profile row is provisioned server-side by the Laravel
    // SupabaseAuth middleware on the first authenticated request (RLS blocks
    // direct inserts via the anon key). full_name is captured during onboarding.
    return { error };
  }, [supabase]);

  // Sign out
  const signOut = useCallback(async () => {
    await supabase.auth.signOut();
    router.push('/login');
  }, [supabase, router]);

  // Reset password
  const resetPassword = useCallback(async (email: string) => {
    const { error } = await supabase.auth.resetPasswordForEmail(email, {
      redirectTo: `${window.location.origin}/reset-password`,
    });

    return { error };
  }, [supabase]);

  // Update profile via the Laravel API (RLS blocks direct table writes).
  const updateProfile = useCallback(async (data: Partial<UserProfile>) => {
    if (!state.user) {
      return { error: new Error('Not authenticated') };
    }

    try {
      const res = await userApi.updateProfile(data);
      const updated = (res.data?.data ?? null) as UserProfile | null;
      setState(prev => ({
        ...prev,
        profile: updated ?? (prev.profile ? { ...prev.profile, ...data } : null),
      }));
      return { error: null };
    } catch (error) {
      return { error: error as Error };
    }
  }, [state.user]);

  return {
    ...state,
    signIn,
    signUp,
    signOut,
    resetPassword,
    updateProfile,
  };
}
