'use client';

import { useState, useCallback } from 'react';
import type { ApiError } from '@/types';

interface UseApiState<T> {
  data: T | null;
  isLoading: boolean;
  error: ApiError | null;
}

interface UseApiReturn<T> extends UseApiState<T> {
  execute: (...args: unknown[]) => Promise<T | null>;
  reset: () => void;
}

/**
 * Generic API hook for handling async operations
 */
export function useApi<T>(
  apiFunction: (...args: unknown[]) => Promise<{ data: T }>
): UseApiReturn<T> {
  const [state, setState] = useState<UseApiState<T>>({
    data: null,
    isLoading: false,
    error: null,
  });

  const execute = useCallback(async (...args: unknown[]): Promise<T | null> => {
    setState(prev => ({ ...prev, isLoading: true, error: null }));

    try {
      const response = await apiFunction(...args);
      setState({ data: response.data, isLoading: false, error: null });
      return response.data;
    } catch (err) {
      const error: ApiError = {
        message: err instanceof Error ? err.message : 'An error occurred',
      };
      setState({ data: null, isLoading: false, error });
      return null;
    }
  }, [apiFunction]);

  const reset = useCallback(() => {
    setState({ data: null, isLoading: false, error: null });
  }, []);

  return {
    ...state,
    execute,
    reset,
  };
}
