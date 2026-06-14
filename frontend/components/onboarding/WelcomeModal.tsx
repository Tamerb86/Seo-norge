'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';

interface WelcomeModalProps {
  userName?: string;
}

export default function WelcomeModal({ userName }: WelcomeModalProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isOpen, setIsOpen] = useState(false);
  const [currentTip, setCurrentTip] = useState(0);

  const tips = [
    {
      icon: '🎯',
      title: 'Spor rangeringene dine',
      description: 'Legg til søkeord og se hvor du rangerer i Google.no. Vi sjekker automatisk hver dag!',
    },
    {
      icon: '🤖',
      title: 'Bruk AI-verktøyene',
      description: 'La AI analysere innholdet ditt og gi deg konkrete forbedringsforslag.',
    },
    {
      icon: '👥',
      title: 'Analyser konkurrentene',
      description: 'Se hvilke søkeord konkurrentene dine rangerer for, og finn muligheter de har gått glipp av.',
    },
    {
      icon: '📊',
      title: 'Følg med på dashbordet',
      description: 'Dashbordet gir deg en rask oversikt over alt som skjer med SEO-en din.',
    },
  ];

  useEffect(() => {
    if (searchParams.get('welcome') === 'true') {
      setIsOpen(true);
      // Remove the query parameter from URL
      window.history.replaceState({}, '', '/dashboard');
    }
  }, [searchParams]);

  const handleClose = () => {
    setIsOpen(false);
  };

  const handleNext = () => {
    if (currentTip < tips.length - 1) {
      setCurrentTip(currentTip + 1);
    } else {
      handleClose();
    }
  };

  const handleStartTour = () => {
    handleClose();
    // Could trigger a product tour here
    router.push('/dashboard/domains');
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div className="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" />

        <div className="relative inline-block w-full max-w-lg p-0 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl">
          {/* Header with gradient */}
          <div className="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-10 text-white text-center">
            <div className="text-5xl mb-4">🎉</div>
            <h2 className="text-2xl font-bold mb-2">
              Velkommen{userName ? `, ${userName}` : ''}!
            </h2>
            <p className="text-blue-100">
              Kontoen din er klar. Her er noen tips for å komme i gang.
            </p>
          </div>

          {/* Tips Carousel */}
          <div className="px-8 py-6">
            <div className="bg-gray-50 rounded-xl p-6 mb-6">
              <div className="flex items-start space-x-4">
                <div className="text-4xl">{tips[currentTip].icon}</div>
                <div>
                  <h3 className="font-semibold text-gray-900 mb-1">
                    {tips[currentTip].title}
                  </h3>
                  <p className="text-gray-600 text-sm">
                    {tips[currentTip].description}
                  </p>
                </div>
              </div>
            </div>

            {/* Dots indicator */}
            <div className="flex justify-center space-x-2 mb-6">
              {tips.map((_, index) => (
                <button
                  key={index}
                  onClick={() => setCurrentTip(index)}
                  className={`w-2 h-2 rounded-full transition-all ${
                    index === currentTip ? 'bg-blue-600 w-6' : 'bg-gray-300'
                  }`}
                />
              ))}
            </div>

            {/* Action Buttons */}
            <div className="flex flex-col space-y-3">
              <button
                onClick={handleStartTour}
                className="w-full px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
              >
                Legg til ditt første nettsted →
              </button>
              <button
                onClick={handleNext}
                className="w-full px-6 py-3 text-gray-600 hover:text-gray-900 font-medium"
              >
                {currentTip < tips.length - 1 ? 'Neste tips' : 'Gå til dashbord'}
              </button>
            </div>
          </div>

          {/* Close button */}
          <button
            onClick={handleClose}
            className="absolute top-4 right-4 text-white/80 hover:text-white"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
}
