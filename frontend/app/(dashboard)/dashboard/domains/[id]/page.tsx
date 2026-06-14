'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { api } from '@/lib/api';

interface Domain {
  id: string;
  domain: string;
  name: string;
  favicon_url: string | null;
  is_verified: boolean;
  created_at: string;
}

interface Keyword {
  id: string;
  keyword: string;
  search_volume: number | null;
  difficulty: number | null;
  cpc: number | null;
  search_intent: string | null;
  latest_ranking: {
    position: number | null;
    url: string | null;
    change: number;
    checked_at: string;
  } | null;
}

export default function DomainDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [domain, setDomain] = useState<Domain | null>(null);
  const [keywords, setKeywords] = useState<Keyword[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showAddKeyword, setShowAddKeyword] = useState(false);
  const [refreshing, setRefreshing] = useState<string | null>(null);

  useEffect(() => {
    fetchData();
  }, [params.id]);

  const fetchData = async () => {
    try {
      const [domainRes, keywordsRes] = await Promise.all([
        api.get(`/domains/${params.id}`),
        api.get(`/domains/${params.id}/keywords`),
      ]);
      setDomain(domainRes.data);
      setKeywords(keywordsRes.data);
    } catch (err) {
      setError('Kunne ikke laste data. Prøv igjen.');
    } finally {
      setLoading(false);
    }
  };

  const handleRefreshRanking = async (keywordId: string) => {
    setRefreshing(keywordId);
    try {
      await api.post(`/domains/${params.id}/keywords/${keywordId}/refresh`);
      await fetchData();
    } catch (err) {
      alert('Kunne ikke oppdatere rangering. Prøv igjen.');
    } finally {
      setRefreshing(null);
    }
  };

  const handleDeleteKeyword = async (keywordId: string) => {
    if (!confirm('Er du sikker på at du vil slette dette søkeordet?')) {
      return;
    }

    try {
      await api.delete(`/domains/${params.id}/keywords/${keywordId}`);
      setKeywords(keywords.filter(k => k.id !== keywordId));
    } catch (err) {
      alert('Kunne ikke slette søkeordet. Prøv igjen.');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error || !domain) {
    return (
      <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {error || 'Nettstedet ble ikke funnet.'}
      </div>
    );
  }

  const keywordsInTop10 = keywords.filter(k => k.latest_ranking?.position && k.latest_ranking.position <= 10).length;
  const keywordsInTop3 = keywords.filter(k => k.latest_ranking?.position && k.latest_ranking.position <= 3).length;
  const avgPosition = keywords.length > 0
    ? keywords.reduce((sum, k) => sum + (k.latest_ranking?.position || 100), 0) / keywords.length
    : null;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <Link href="/dashboard/domains" className="text-gray-400 hover:text-gray-500">
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </Link>
          <div className="flex items-center space-x-3">
            <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
              {domain.favicon_url ? (
                <img src={domain.favicon_url} alt="" className="w-8 h-8" />
              ) : (
                <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
              )}
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900">{domain.name || domain.domain}</h1>
              <p className="text-gray-500">{domain.domain}</p>
            </div>
          </div>
        </div>
        <button
          onClick={() => setShowAddKeyword(true)}
          className="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
        >
          <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          Legg til søkeord
        </button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <p className="text-sm text-gray-500">Totalt søkeord</p>
          <p className="text-2xl font-bold text-gray-900">{keywords.length}</p>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <p className="text-sm text-gray-500">I topp 3</p>
          <p className="text-2xl font-bold text-green-600">{keywordsInTop3}</p>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <p className="text-sm text-gray-500">I topp 10</p>
          <p className="text-2xl font-bold text-blue-600">{keywordsInTop10}</p>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <p className="text-sm text-gray-500">Gj.snitt posisjon</p>
          <p className="text-2xl font-bold text-gray-900">{avgPosition?.toFixed(1) || '-'}</p>
        </div>
      </div>

      {/* Keywords Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Søkeord</h2>
        </div>

        {keywords.length === 0 ? (
          <div className="p-12 text-center">
            <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <h3 className="text-lg font-medium text-gray-900 mb-2">Ingen søkeord ennå</h3>
            <p className="text-gray-500 mb-4">Legg til søkeord for å begynne å spore rangeringer</p>
            <button
              onClick={() => setShowAddKeyword(true)}
              className="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
            >
              Legg til søkeord
            </button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Søkeord</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posisjon</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endring</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volum</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vanskelighet</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Handlinger</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {keywords.map((keyword) => (
                  <tr key={keyword.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="font-medium text-gray-900">{keyword.keyword}</span>
                      {keyword.search_intent && (
                        <span className={`ml-2 inline-flex px-2 py-0.5 rounded text-xs font-medium ${
                          keyword.search_intent === 'transactional' ? 'bg-green-100 text-green-800' :
                          keyword.search_intent === 'commercial' ? 'bg-yellow-100 text-yellow-800' :
                          keyword.search_intent === 'informational' ? 'bg-blue-100 text-blue-800' :
                          'bg-gray-100 text-gray-800'
                        }`}>
                          {keyword.search_intent}
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {keyword.latest_ranking?.position ? (
                        <span className={`text-lg font-bold ${
                          keyword.latest_ranking.position <= 3 ? 'text-green-600' :
                          keyword.latest_ranking.position <= 10 ? 'text-blue-600' :
                          keyword.latest_ranking.position <= 20 ? 'text-yellow-600' :
                          'text-gray-600'
                        }`}>
                          #{keyword.latest_ranking.position}
                        </span>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {keyword.latest_ranking?.change !== undefined && keyword.latest_ranking.change !== 0 ? (
                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                          keyword.latest_ranking.change > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                        }`}>
                          {keyword.latest_ranking.change > 0 ? '↑' : '↓'} {Math.abs(keyword.latest_ranking.change)}
                        </span>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {keyword.search_volume?.toLocaleString() || '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {keyword.difficulty !== null ? (
                        <span className={`inline-flex px-2 py-1 rounded-full text-xs font-medium ${
                          keyword.difficulty <= 30 ? 'bg-green-100 text-green-800' :
                          keyword.difficulty <= 60 ? 'bg-yellow-100 text-yellow-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {keyword.difficulty}
                        </span>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    <td className="px-6 py-4 max-w-xs truncate text-sm text-gray-500">
                      {keyword.latest_ranking?.url ? (
                        <a href={keyword.latest_ranking.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                          {keyword.latest_ranking.url.replace(/^https?:\/\/[^/]+/, '')}
                        </a>
                      ) : (
                        '-'
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <button
                        onClick={() => handleRefreshRanking(keyword.id)}
                        disabled={refreshing === keyword.id}
                        className="text-blue-600 hover:text-blue-900 mr-3 disabled:opacity-50"
                      >
                        {refreshing === keyword.id ? (
                          <svg className="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                          </svg>
                        ) : (
                          'Oppdater'
                        )}
                      </button>
                      <button
                        onClick={() => handleDeleteKeyword(keyword.id)}
                        className="text-red-600 hover:text-red-900"
                      >
                        Slett
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Add Keyword Modal */}
      {showAddKeyword && (
        <AddKeywordModal
          domainId={domain.id}
          onClose={() => setShowAddKeyword(false)}
          onSuccess={() => {
            setShowAddKeyword(false);
            fetchData();
          }}
        />
      )}
    </div>
  );
}

function AddKeywordModal({ domainId, onClose, onSuccess }: { domainId: string; onClose: () => void; onSuccess: () => void }) {
  const [keywords, setKeywords] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const keywordList = keywords
        .split('\n')
        .map(k => k.trim())
        .filter(k => k.length > 0);

      if (keywordList.length === 0) {
        setError('Vennligst skriv inn minst ett søkeord.');
        setLoading(false);
        return;
      }

      if (keywordList.length === 1) {
        await api.post(`/domains/${domainId}/keywords`, {
          keyword: keywordList[0],
        });
      } else {
        await api.post(`/domains/${domainId}/keywords/bulk`, {
          keywords: keywordList,
        });
      }

      onSuccess();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Kunne ikke legge til søkeord. Prøv igjen.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onClick={onClose} />

        <div className="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-900">Legg til søkeord</h3>
            <button onClick={onClose} className="text-gray-400 hover:text-gray-500">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700">
                Søkeord *
              </label>
              <textarea
                value={keywords}
                onChange={(e) => setKeywords(e.target.value)}
                required
                rows={6}
                placeholder="Skriv ett søkeord per linje&#10;&#10;Eksempel:&#10;seo verktøy&#10;beste seo tips&#10;søkemotoroptimalisering"
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              />
              <p className="mt-1 text-xs text-gray-500">
                Du kan legge til flere søkeord ved å skrive ett per linje.
              </p>
            </div>

            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {error}
              </div>
            )}

            <div className="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
              >
                Avbryt
              </button>
              <button
                type="submit"
                disabled={loading || !keywords.trim()}
                className="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? 'Legger til...' : 'Legg til'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
