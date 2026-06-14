'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/lib/api';

interface OnboardingStep {
  id: number;
  title: string;
  description: string;
  icon: string;
  completed: boolean;
}

export default function OnboardingPage() {
  const router = useRouter();
  const [currentStep, setCurrentStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    // Step 1: Profile
    fullName: '',
    company: '',
    role: '',
    // Step 2: First Domain
    domain: '',
    domainName: '',
    // Step 3: First Keywords
    keywords: '',
    // Step 4: Competitors
    competitors: '',
    // Step 5: Goals
    goals: [] as string[],
    experience: '',
  });

  const steps: OnboardingStep[] = [
    {
      id: 1,
      title: 'Velkommen!',
      description: 'La oss bli kjent med deg',
      icon: '👋',
      completed: currentStep > 1,
    },
    {
      id: 2,
      title: 'Legg til nettsted',
      description: 'Hvilket nettsted vil du optimalisere?',
      icon: '🌐',
      completed: currentStep > 2,
    },
    {
      id: 3,
      title: 'Søkeord',
      description: 'Hvilke søkeord vil du spore?',
      icon: '🔍',
      completed: currentStep > 3,
    },
    {
      id: 4,
      title: 'Konkurrenter',
      description: 'Hvem konkurrerer du med?',
      icon: '👥',
      completed: currentStep > 4,
    },
    {
      id: 5,
      title: 'Dine mål',
      description: 'Hva ønsker du å oppnå?',
      icon: '🎯',
      completed: currentStep > 5,
    },
  ];

  const handleNext = async () => {
    if (currentStep < 5) {
      setCurrentStep(currentStep + 1);
    } else {
      await completeOnboarding();
    }
  };

  const handleBack = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleSkip = () => {
    if (currentStep < 5) {
      setCurrentStep(currentStep + 1);
    } else {
      completeOnboarding();
    }
  };

  const completeOnboarding = async () => {
    setLoading(true);
    try {
      // Save profile
      await api.put('/user/profile', {
        full_name: formData.fullName,
        company: formData.company,
        role: formData.role,
        onboarding_completed: true,
      });

      // Create domain if provided
      if (formData.domain) {
        const domainRes = await api.post('/domains', {
          domain: formData.domain.replace(/^https?:\/\//, '').replace(/\/.*$/, ''),
          name: formData.domainName || formData.domain,
        });

        // Add keywords if provided
        if (formData.keywords) {
          const keywordList = formData.keywords
            .split('\n')
            .map(k => k.trim())
            .filter(k => k.length > 0);

          if (keywordList.length > 0) {
            await api.post(`/domains/${domainRes.data.id}/keywords/bulk`, {
              keywords: keywordList,
            });
          }
        }

        // Add competitors if provided
        if (formData.competitors) {
          const competitorList = formData.competitors
            .split('\n')
            .map(c => c.trim())
            .filter(c => c.length > 0);

          for (const competitor of competitorList) {
            await api.post(`/domains/${domainRes.data.id}/competitors`, {
              competitor_domain: competitor.replace(/^https?:\/\//, '').replace(/\/.*$/, ''),
            });
          }
        }
      }

      // Save goals
      await api.post('/user/goals', {
        goals: formData.goals,
        experience: formData.experience,
      });

      router.push('/dashboard?welcome=true');
    } catch (error) {
      console.error('Onboarding error:', error);
      alert('Det oppstod en feil. Vennligst prøv igjen.');
    } finally {
      setLoading(false);
    }
  };

  const toggleGoal = (goal: string) => {
    setFormData(prev => ({
      ...prev,
      goals: prev.goals.includes(goal)
        ? prev.goals.filter(g => g !== goal)
        : [...prev.goals, goal],
    }));
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
      <div className="max-w-3xl mx-auto px-4 py-12">
        {/* Progress Bar */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-2">
            {steps.map((step, index) => (
              <div key={step.id} className="flex items-center">
                <div
                  className={`w-10 h-10 rounded-full flex items-center justify-center text-lg ${
                    step.completed
                      ? 'bg-green-500 text-white'
                      : currentStep === step.id
                      ? 'bg-blue-600 text-white'
                      : 'bg-gray-200 text-gray-500'
                  }`}
                >
                  {step.completed ? '✓' : step.icon}
                </div>
                {index < steps.length - 1 && (
                  <div
                    className={`w-16 h-1 mx-2 ${
                      step.completed ? 'bg-green-500' : 'bg-gray-200'
                    }`}
                  />
                )}
              </div>
            ))}
          </div>
          <p className="text-center text-sm text-gray-600">
            Steg {currentStep} av {steps.length}: {steps[currentStep - 1].title}
          </p>
        </div>

        {/* Step Content */}
        <div className="bg-white rounded-2xl shadow-xl p-8">
          {/* Step 1: Welcome & Profile */}
          {currentStep === 1 && (
            <div className="space-y-6">
              <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                  Velkommen til SEO Norge! 🇳🇴
                </h1>
                <p className="text-gray-600">
                  La oss sette opp kontoen din på noen få minutter
                </p>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Fullt navn *
                  </label>
                  <input
                    type="text"
                    value={formData.fullName}
                    onChange={(e) => setFormData({ ...formData, fullName: e.target.value })}
                    placeholder="Ola Nordmann"
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Bedrift / Organisasjon
                  </label>
                  <input
                    type="text"
                    value={formData.company}
                    onChange={(e) => setFormData({ ...formData, company: e.target.value })}
                    placeholder="Min Bedrift AS"
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Din rolle
                  </label>
                  <select
                    value={formData.role}
                    onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">Velg din rolle...</option>
                    <option value="owner">Bedriftseier</option>
                    <option value="marketing">Markedsføring</option>
                    <option value="seo">SEO-spesialist</option>
                    <option value="developer">Utvikler</option>
                    <option value="agency">Byrå</option>
                    <option value="freelancer">Frilanser</option>
                    <option value="other">Annet</option>
                  </select>
                </div>
              </div>
            </div>
          )}

          {/* Step 2: Add Domain */}
          {currentStep === 2 && (
            <div className="space-y-6">
              <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                  Legg til ditt første nettsted 🌐
                </h1>
                <p className="text-gray-600">
                  Hvilket nettsted vil du optimalisere for søkemotorer?
                </p>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Nettstedets URL *
                  </label>
                  <input
                    type="text"
                    value={formData.domain}
                    onChange={(e) => setFormData({ ...formData, domain: e.target.value })}
                    placeholder="minbedrift.no"
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    Skriv inn domenet uten https:// eller www.
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Nettstedets navn (valgfritt)
                  </label>
                  <input
                    type="text"
                    value={formData.domainName}
                    onChange={(e) => setFormData({ ...formData, domainName: e.target.value })}
                    placeholder="Min Bedrift"
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div className="bg-blue-50 rounded-lg p-4">
                <h3 className="font-medium text-blue-900 mb-2">💡 Tips</h3>
                <p className="text-sm text-blue-700">
                  Du kan legge til flere nettsteder senere fra dashbordet. 
                  Start med det viktigste nettstedet ditt først.
                </p>
              </div>
            </div>
          )}

          {/* Step 3: Keywords */}
          {currentStep === 3 && (
            <div className="space-y-6">
              <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                  Hvilke søkeord vil du spore? 🔍
                </h1>
                <p className="text-gray-600">
                  Legg til søkeordene du vil rangere for i Google
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Søkeord (ett per linje)
                </label>
                <textarea
                  value={formData.keywords}
                  onChange={(e) => setFormData({ ...formData, keywords: e.target.value })}
                  rows={8}
                  placeholder="beste restaurant oslo&#10;romantisk middag&#10;fine dining oslo&#10;italiensk restaurant&#10;sushi oslo sentrum"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p className="mt-1 text-xs text-gray-500">
                  Skriv ett søkeord per linje. Du kan legge til flere senere.
                </p>
              </div>

              <div className="bg-yellow-50 rounded-lg p-4">
                <h3 className="font-medium text-yellow-900 mb-2">💡 Forslag til søkeord</h3>
                <p className="text-sm text-yellow-700 mb-2">
                  Tenk på hva kundene dine søker etter:
                </p>
                <ul className="text-sm text-yellow-700 list-disc list-inside space-y-1">
                  <li>Produkter/tjenester + by (f.eks. "tannlege oslo")</li>
                  <li>Problemer du løser (f.eks. "hvordan fjerne flekker")</li>
                  <li>Sammenligninger (f.eks. "beste [produkt] 2024")</li>
                </ul>
              </div>
            </div>
          )}

          {/* Step 4: Competitors */}
          {currentStep === 4 && (
            <div className="space-y-6">
              <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                  Hvem er konkurrentene dine? 👥
                </h1>
                <p className="text-gray-600">
                  Vi hjelper deg med å analysere og overgå dem
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Konkurrenters nettsteder (ett per linje)
                </label>
                <textarea
                  value={formData.competitors}
                  onChange={(e) => setFormData({ ...formData, competitors: e.target.value })}
                  rows={6}
                  placeholder="konkurrent1.no&#10;konkurrent2.no&#10;konkurrent3.no"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p className="mt-1 text-xs text-gray-500">
                  Skriv inn domenene til dine hovedkonkurrenter.
                </p>
              </div>

              <div className="bg-green-50 rounded-lg p-4">
                <h3 className="font-medium text-green-900 mb-2">🎯 Hvorfor spore konkurrenter?</h3>
                <ul className="text-sm text-green-700 space-y-1">
                  <li>✓ Se hvilke søkeord de rangerer for</li>
                  <li>✓ Finn søkeord de har glemt (din mulighet!)</li>
                  <li>✓ Lær av deres beste innhold</li>
                  <li>✓ Få varsler når de overgår deg</li>
                </ul>
              </div>
            </div>
          )}

          {/* Step 5: Goals */}
          {currentStep === 5 && (
            <div className="space-y-6">
              <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                  Hva ønsker du å oppnå? 🎯
                </h1>
                <p className="text-gray-600">
                  Vi tilpasser opplevelsen basert på dine mål
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-3">
                  Velg dine hovedmål (velg alle som passer)
                </label>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {[
                    { id: 'traffic', label: 'Øke trafikk til nettstedet', icon: '📈' },
                    { id: 'rankings', label: 'Forbedre Google-rangeringer', icon: '🏆' },
                    { id: 'leads', label: 'Generere flere leads', icon: '🎯' },
                    { id: 'sales', label: 'Øke salg/konverteringer', icon: '💰' },
                    { id: 'local', label: 'Bli mer synlig lokalt', icon: '📍' },
                    { id: 'brand', label: 'Bygge merkevarebevissthet', icon: '⭐' },
                    { id: 'content', label: 'Lage bedre innhold', icon: '✍️' },
                    { id: 'compete', label: 'Overgå konkurrentene', icon: '🥇' },
                  ].map((goal) => (
                    <button
                      key={goal.id}
                      type="button"
                      onClick={() => toggleGoal(goal.id)}
                      className={`flex items-center p-4 rounded-lg border-2 transition-all ${
                        formData.goals.includes(goal.id)
                          ? 'border-blue-500 bg-blue-50'
                          : 'border-gray-200 hover:border-gray-300'
                      }`}
                    >
                      <span className="text-2xl mr-3">{goal.icon}</span>
                      <span className={`text-sm font-medium ${
                        formData.goals.includes(goal.id) ? 'text-blue-700' : 'text-gray-700'
                      }`}>
                        {goal.label}
                      </span>
                      {formData.goals.includes(goal.id) && (
                        <span className="ml-auto text-blue-500">✓</span>
                      )}
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Hva er din SEO-erfaring?
                </label>
                <select
                  value={formData.experience}
                  onChange={(e) => setFormData({ ...formData, experience: e.target.value })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Velg...</option>
                  <option value="beginner">Nybegynner - Jeg er ny med SEO</option>
                  <option value="intermediate">Middels - Jeg kan det grunnleggende</option>
                  <option value="advanced">Avansert - Jeg jobber med SEO daglig</option>
                  <option value="expert">Ekspert - SEO er min hovedjobb</option>
                </select>
              </div>
            </div>
          )}

          {/* Navigation Buttons */}
          <div className="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
            <button
              type="button"
              onClick={handleBack}
              disabled={currentStep === 1}
              className={`px-6 py-2 rounded-lg font-medium ${
                currentStep === 1
                  ? 'text-gray-400 cursor-not-allowed'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              ← Tilbake
            </button>

            <div className="flex items-center space-x-3">
              <button
                type="button"
                onClick={handleSkip}
                className="px-6 py-2 text-gray-500 hover:text-gray-700 font-medium"
              >
                Hopp over
              </button>
              <button
                type="button"
                onClick={handleNext}
                disabled={loading}
                className="px-8 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? (
                  <span className="flex items-center">
                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    Lagrer...
                  </span>
                ) : currentStep === 5 ? (
                  'Fullfør oppsett →'
                ) : (
                  'Neste →'
                )}
              </button>
            </div>
          </div>
        </div>

        {/* Help Text */}
        <p className="text-center text-sm text-gray-500 mt-6">
          Trenger du hjelp? <a href="mailto:support@seo-norge.no" className="text-blue-600 hover:underline">Kontakt oss</a>
        </p>
      </div>
    </div>
  );
}
