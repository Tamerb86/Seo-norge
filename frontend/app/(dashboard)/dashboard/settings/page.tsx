'use client';

import { useEffect, useState } from 'react';
import { api } from '@/lib/api';
import { supabase } from '@/lib/supabase';

interface UserProfile {
  id: string;
  email: string;
  full_name: string;
  company: string;
  plan: string;
  created_at: string;
}

export default function SettingsPage() {
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const [formData, setFormData] = useState({
    full_name: '',
    company: '',
  });

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await api.get('/user/profile');
      setProfile(response.data);
      setFormData({
        full_name: response.data.full_name || '',
        company: response.data.company || '',
      });
    } catch (err) {
      setError('Kunne ikke laste profil. Prøv igjen.');
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      await api.put('/user/profile', formData);
      setSuccess('Profilen ble oppdatert.');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError('Kunne ikke oppdatere profil. Prøv igjen.');
    } finally {
      setSaving(false);
    }
  };

  const handlePasswordChange = async () => {
    try {
      const { error } = await supabase.auth.resetPasswordForEmail(profile?.email || '', {
        redirectTo: `${window.location.origin}/auth/reset-password`,
      });

      if (error) {
        setError('Kunne ikke sende tilbakestillingslenke. Prøv igjen.');
      } else {
        setSuccess('En e-post med tilbakestillingslenke er sendt til din e-postadresse.');
      }
    } catch (err) {
      setError('En feil oppstod. Prøv igjen.');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-3xl">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Innstillinger</h1>
        <p className="text-gray-600">Administrer kontoen din og preferanser</p>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          {error}
        </div>
      )}

      {success && (
        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
          {success}
        </div>
      )}

      {/* Profile Section */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Profil</h2>
        
        <form onSubmit={handleSave} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700">
              E-postadresse
            </label>
            <input
              type="email"
              value={profile?.email || ''}
              disabled
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
            />
            <p className="mt-1 text-xs text-gray-500">
              E-postadressen kan ikke endres.
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700">
              Fullt navn
            </label>
            <input
              type="text"
              value={formData.full_name}
              onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700">
              Bedrift
            </label>
            <input
              type="text"
              value={formData.company}
              onChange={(e) => setFormData({ ...formData, company: e.target.value })}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div className="pt-4">
            <button
              type="submit"
              disabled={saving}
              className="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
            >
              {saving ? 'Lagrer...' : 'Lagre endringer'}
            </button>
          </div>
        </form>
      </div>

      {/* Password Section */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Passord</h2>
        <p className="text-sm text-gray-500 mb-4">
          Klikk på knappen nedenfor for å motta en e-post med lenke for å endre passordet ditt.
        </p>
        <button
          onClick={handlePasswordChange}
          className="px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Endre passord
        </button>
      </div>

      {/* Plan Section */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-gray-900">Abonnement</h2>
          <span className={`inline-flex px-3 py-1 rounded-full text-sm font-medium ${
            profile?.plan === 'agency' ? 'bg-purple-100 text-purple-800' :
            profile?.plan === 'professional' ? 'bg-blue-100 text-blue-800' :
            profile?.plan === 'starter' ? 'bg-green-100 text-green-800' :
            'bg-gray-100 text-gray-800'
          }`}>
            {profile?.plan === 'agency' ? 'Byrå' :
             profile?.plan === 'professional' ? 'Profesjonell' :
             profile?.plan === 'starter' ? 'Starter' : 'Gratis'}
          </span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <PlanCard
            name="Starter"
            price="299"
            features={['3 nettsteder', '100 søkeord', '100 AI-analyser/mnd', 'Daglig oppdatering', 'E-postvarsler']}
            current={profile?.plan === 'starter'}
          />
          <PlanCard
            name="Profesjonell"
            price="799"
            features={['10 nettsteder', '500 søkeord', '500 AI-analyser/mnd', 'Konkurrentanalyse', 'API-tilgang', 'Prioritert støtte']}
            current={profile?.plan === 'professional'}
            popular
          />
          <PlanCard
            name="Byrå"
            price="1999"
            features={['Ubegrenset nettsteder', 'Ubegrenset søkeord', 'Ubegrenset AI-analyser', 'White-label', 'Dedikert support']}
            current={profile?.plan === 'agency'}
          />
        </div>

        <div className="flex space-x-4">
          <a
            href="/dashboard/billing"
            className="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
          >
            Oppgrader plan
          </a>
          <button
            onClick={async () => {
              try {
                const response = await api.post('/billing/portal');
                window.location.href = response.data.url;
              } catch (err) {
                setError('Kunne ikke åpne faktureringsportalen. Prøv igjen.');
              }
            }}
            className="px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            Administrer fakturering
          </button>
        </div>
      </div>

      {/* Danger Zone */}
      <div className="bg-white rounded-xl shadow-sm border border-red-200 p-6">
        <h2 className="text-lg font-semibold text-red-600 mb-4">Faresone</h2>
        <p className="text-sm text-gray-500 mb-4">
          Når du sletter kontoen din, vil alle data bli permanent fjernet. Denne handlingen kan ikke angres.
        </p>
        <button
          onClick={() => {
            if (confirm('Er du sikker på at du vil slette kontoen din? Alle data vil bli permanent fjernet.')) {
              // Handle account deletion
              alert('Vennligst kontakt support for å slette kontoen din.');
            }
          }}
          className="px-4 py-2 border border-red-300 rounded-lg shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50"
        >
          Slett konto
        </button>
      </div>
    </div>
  );
}

function PlanCard({ name, price, features, current, popular }: {
  name: string;
  price: string;
  features: string[];
  current?: boolean;
  popular?: boolean;
}) {
  return (
    <div className={`relative rounded-lg border p-4 ${
      current ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
    }`}>
      {popular && (
        <span className="absolute -top-2 left-1/2 -translate-x-1/2 inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">
          Mest populær
        </span>
      )}
      <h3 className="font-semibold text-gray-900">{name}</h3>
      <p className="mt-1">
        <span className="text-2xl font-bold text-gray-900">{price}</span>
        <span className="text-sm text-gray-500"> kr/mnd</span>
      </p>
      <ul className="mt-3 space-y-1">
        {features.map((feature, i) => (
          <li key={i} className="text-xs text-gray-600 flex items-center">
            <svg className="w-3 h-3 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
            </svg>
            {feature}
          </li>
        ))}
      </ul>
      {current && (
        <span className="mt-3 block text-xs font-medium text-blue-600">Nåværende plan</span>
      )}
    </div>
  );
}
