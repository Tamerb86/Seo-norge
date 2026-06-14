'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { api } from '@/lib/api';

interface ChecklistItem {
  id: string;
  title: string;
  description: string;
  href: string;
  completed: boolean;
  icon: string;
}

export default function OnboardingChecklist() {
  const [items, setItems] = useState<ChecklistItem[]>([
    {
      id: 'profile',
      title: 'Fullfør profilen din',
      description: 'Legg til navn og bedriftsinformasjon',
      href: '/dashboard/settings',
      completed: false,
      icon: '👤',
    },
    {
      id: 'domain',
      title: 'Legg til et nettsted',
      description: 'Legg til nettstedet du vil optimalisere',
      href: '/dashboard/domains',
      completed: false,
      icon: '🌐',
    },
    {
      id: 'keywords',
      title: 'Legg til søkeord',
      description: 'Legg til minst 5 søkeord å spore',
      href: '/dashboard/domains',
      completed: false,
      icon: '🔍',
    },
    {
      id: 'competitor',
      title: 'Legg til en konkurrent',
      description: 'Analyser en konkurrents SEO-strategi',
      href: '/dashboard/domains',
      completed: false,
      icon: '👥',
    },
    {
      id: 'ai',
      title: 'Prøv AI-verktøyene',
      description: 'Analyser en side med AI',
      href: '/dashboard/ai',
      completed: false,
      icon: '🤖',
    },
  ]);

  const [isExpanded, setIsExpanded] = useState(true);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchProgress();
  }, []);

  const fetchProgress = async () => {
    try {
      const response = await api.get('/user/onboarding-progress');
      const progress = response.data;

      setItems(prev => prev.map(item => ({
        ...item,
        completed: progress[item.id] || false,
      })));
    } catch (error) {
      console.error('Error fetching onboarding progress:', error);
    } finally {
      setLoading(false);
    }
  };

  const completedCount = items.filter(item => item.completed).length;
  const progress = (completedCount / items.length) * 100;
  const allCompleted = completedCount === items.length;

  const dismissChecklist = async () => {
    try {
      await api.post('/user/dismiss-checklist');
      setIsExpanded(false);
    } catch (error) {
      console.error('Error dismissing checklist:', error);
    }
  };

  if (loading) {
    return null;
  }

  if (allCompleted && !isExpanded) {
    return null;
  }

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
      {/* Header */}
      <div
        className="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 cursor-pointer"
        onClick={() => setIsExpanded(!isExpanded)}
      >
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="text-2xl">🚀</div>
            <div>
              <h3 className="font-semibold text-gray-900">
                {allCompleted ? 'Gratulerer! Du er klar!' : 'Kom i gang med SEO Norge'}
              </h3>
              <p className="text-sm text-gray-600">
                {completedCount} av {items.length} oppgaver fullført
              </p>
            </div>
          </div>
          <div className="flex items-center space-x-4">
            {/* Progress Circle */}
            <div className="relative w-12 h-12">
              <svg className="w-12 h-12 transform -rotate-90">
                <circle
                  cx="24"
                  cy="24"
                  r="20"
                  stroke="#E5E7EB"
                  strokeWidth="4"
                  fill="none"
                />
                <circle
                  cx="24"
                  cy="24"
                  r="20"
                  stroke={allCompleted ? '#10B981' : '#3B82F6'}
                  strokeWidth="4"
                  fill="none"
                  strokeDasharray={`${progress * 1.256} 125.6`}
                  className="transition-all duration-500"
                />
              </svg>
              <span className="absolute inset-0 flex items-center justify-center text-sm font-semibold text-gray-700">
                {Math.round(progress)}%
              </span>
            </div>
            {/* Expand/Collapse */}
            <svg
              className={`w-5 h-5 text-gray-400 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>

      {/* Checklist Items */}
      {isExpanded && (
        <div className="divide-y divide-gray-100">
          {items.map((item, index) => (
            <Link
              key={item.id}
              href={item.href}
              className={`flex items-center px-6 py-4 hover:bg-gray-50 transition-colors ${
                item.completed ? 'opacity-60' : ''
              }`}
            >
              <div className="flex items-center space-x-4 flex-1">
                {/* Status Icon */}
                <div
                  className={`w-8 h-8 rounded-full flex items-center justify-center ${
                    item.completed
                      ? 'bg-green-100 text-green-600'
                      : 'bg-gray-100 text-gray-400'
                  }`}
                >
                  {item.completed ? (
                    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                  ) : (
                    <span className="text-lg">{item.icon}</span>
                  )}
                </div>

                {/* Content */}
                <div>
                  <p className={`font-medium ${item.completed ? 'text-gray-500 line-through' : 'text-gray-900'}`}>
                    {item.title}
                  </p>
                  <p className="text-sm text-gray-500">{item.description}</p>
                </div>
              </div>

              {/* Arrow */}
              {!item.completed && (
                <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              )}
            </Link>
          ))}

          {/* Dismiss Button */}
          {allCompleted && (
            <div className="px-6 py-4 bg-green-50">
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <span className="text-2xl">🎉</span>
                  <span className="font-medium text-green-800">
                    Flott jobbet! Du har fullført alle oppgavene.
                  </span>
                </div>
                <button
                  onClick={dismissChecklist}
                  className="text-sm text-green-600 hover:text-green-800 font-medium"
                >
                  Skjul sjekkliste
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
