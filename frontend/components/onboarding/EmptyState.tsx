'use client';

import Link from 'next/link';

interface EmptyStateProps {
  type: 'domains' | 'keywords' | 'competitors' | 'rankings' | 'reports';
  onAction?: () => void;
}

const emptyStates = {
  domains: {
    icon: '🌐',
    title: 'Ingen nettsteder ennå',
    description: 'Legg til ditt første nettsted for å begynne å spore SEO-ytelsen.',
    actionLabel: 'Legg til nettsted',
    tips: [
      'Start med ditt viktigste nettsted',
      'Du kan legge til flere nettsteder senere',
      'Bruk domenet uten www. eller https://',
    ],
  },
  keywords: {
    icon: '🔍',
    title: 'Ingen søkeord å spore',
    description: 'Legg til søkeord for å se hvor du rangerer i Google.',
    actionLabel: 'Legg til søkeord',
    tips: [
      'Tenk på hva kundene dine søker etter',
      'Inkluder både korte og lange søkeord',
      'Legg til by/sted for lokale søk',
    ],
  },
  competitors: {
    icon: '👥',
    title: 'Ingen konkurrenter lagt til',
    description: 'Legg til konkurrenter for å analysere deres SEO-strategi.',
    actionLabel: 'Legg til konkurrent',
    tips: [
      'Legg til 3-5 hovedkonkurrenter',
      'Inkluder både direkte og indirekte konkurrenter',
      'Se hvilke søkeord de rangerer for',
    ],
  },
  rankings: {
    icon: '📊',
    title: 'Ingen rangeringsdata ennå',
    description: 'Rangeringene sjekkes automatisk hver dag. Kom tilbake i morgen!',
    actionLabel: 'Sjekk nå',
    tips: [
      'Første sjekk skjer innen 24 timer',
      'Du får varsler ved store endringer',
      'Historikk bygges opp over tid',
    ],
  },
  reports: {
    icon: '📈',
    title: 'Ingen rapporter ennå',
    description: 'Rapporter genereres automatisk når du har nok data.',
    actionLabel: 'Se dashboard',
    tips: [
      'Ukentlige rapporter sendes på e-post',
      'Månedlige rapporter gir dypere innsikt',
      'Du kan eksportere rapporter som PDF',
    ],
  },
};

export default function EmptyState({ type, onAction }: EmptyStateProps) {
  const state = emptyStates[type];

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12">
      <div className="max-w-md mx-auto text-center">
        {/* Icon */}
        <div className="text-6xl mb-6">{state.icon}</div>

        {/* Title & Description */}
        <h3 className="text-xl font-semibold text-gray-900 mb-2">
          {state.title}
        </h3>
        <p className="text-gray-600 mb-6">
          {state.description}
        </p>

        {/* Action Button */}
        <button
          onClick={onAction}
          className="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors mb-8"
        >
          <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          {state.actionLabel}
        </button>

        {/* Tips */}
        <div className="bg-blue-50 rounded-lg p-4 text-left">
          <h4 className="font-medium text-blue-900 mb-2 flex items-center">
            <span className="mr-2">💡</span>
            Tips for å komme i gang
          </h4>
          <ul className="space-y-1">
            {state.tips.map((tip, index) => (
              <li key={index} className="text-sm text-blue-700 flex items-start">
                <span className="mr-2">•</span>
                {tip}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
}
