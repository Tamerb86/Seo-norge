'use client';

import { useState } from 'react';
import { api } from '@/lib/api';

type TabType = 'analyze' | 'keywords' | 'generate';

export default function AiToolsPage() {
  const [activeTab, setActiveTab] = useState<TabType>('analyze');

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">AI-verktøy</h1>
        <p className="text-gray-600">Bruk kunstig intelligens for å forbedre din SEO</p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          <TabButton
            active={activeTab === 'analyze'}
            onClick={() => setActiveTab('analyze')}
          >
            Analyser innhold
          </TabButton>
          <TabButton
            active={activeTab === 'keywords'}
            onClick={() => setActiveTab('keywords')}
          >
            Finn søkeord
          </TabButton>
          <TabButton
            active={activeTab === 'generate'}
            onClick={() => setActiveTab('generate')}
          >
            Generer innhold
          </TabButton>
        </nav>
      </div>

      {/* Tab Content */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        {activeTab === 'analyze' && <ContentAnalyzer />}
        {activeTab === 'keywords' && <KeywordSuggester />}
        {activeTab === 'generate' && <ContentGenerator />}
      </div>
    </div>
  );
}

function TabButton({ children, active, onClick }: { children: React.ReactNode; active: boolean; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className={`py-4 px-1 border-b-2 font-medium text-sm ${
        active
          ? 'border-blue-500 text-blue-600'
          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
      }`}
    >
      {children}
    </button>
  );
}

function ContentAnalyzer() {
  const [url, setUrl] = useState('');
  const [content, setContent] = useState('');
  const [keyword, setKeyword] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);

  const handleAnalyze = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setResult(null);

    try {
      const response = await api.post('/ai/analyze-content', {
        url: url || undefined,
        content: content || undefined,
        keyword,
      });
      setResult(response.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'En feil oppstod. Prøv igjen.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-gray-900">Analyser innhold</h2>
        <p className="text-sm text-gray-500">
          Få AI-drevne anbefalinger for å forbedre SEO-ytelsen til innholdet ditt
        </p>
      </div>

      <form onSubmit={handleAnalyze} className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            URL (valgfritt)
          </label>
          <input
            type="url"
            value={url}
            onChange={(e) => setUrl(e.target.value)}
            placeholder="https://eksempel.no/side"
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Eller lim inn innhold
          </label>
          <textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            rows={6}
            placeholder="Lim inn innholdet du vil analysere..."
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Målsøkeord *
          </label>
          <input
            type="text"
            value={keyword}
            onChange={(e) => setKeyword(e.target.value)}
            required
            placeholder="f.eks. beste seo verktøy"
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {error}
          </div>
        )}

        <button
          type="submit"
          disabled={loading || (!url && !content) || !keyword}
          className="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {loading ? 'Analyserer...' : 'Analyser innhold'}
        </button>
      </form>

      {result && (
        <div className="mt-6 space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-gray-900">Resultater</h3>
            <div className="flex items-center space-x-2">
              <span className="text-sm text-gray-500">SEO-score:</span>
              <span className={`text-2xl font-bold ${
                result.score >= 80 ? 'text-green-600' :
                result.score >= 60 ? 'text-yellow-600' : 'text-red-600'
              }`}>
                {result.score}/100
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {result.title_analysis && (
              <AnalysisCard
                title="Tittel"
                score={result.title_analysis.score}
                suggestions={result.title_analysis.suggestions}
              />
            )}
            {result.meta_description && (
              <AnalysisCard
                title="Meta-beskrivelse"
                score={result.meta_description.score}
                suggestions={result.meta_description.suggestions}
              />
            )}
          </div>

          {result.overall_suggestions && result.overall_suggestions.length > 0 && (
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h4 className="font-medium text-blue-900 mb-2">Anbefalinger</h4>
              <ul className="list-disc list-inside space-y-1 text-sm text-blue-800">
                {result.overall_suggestions.map((suggestion: string, index: number) => (
                  <li key={index}>{suggestion}</li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

function AnalysisCard({ title, score, suggestions }: { title: string; score: number; suggestions: string[] }) {
  return (
    <div className="bg-gray-50 rounded-lg p-4">
      <div className="flex items-center justify-between mb-2">
        <h4 className="font-medium text-gray-900">{title}</h4>
        <span className={`text-sm font-medium ${
          score >= 80 ? 'text-green-600' :
          score >= 60 ? 'text-yellow-600' : 'text-red-600'
        }`}>
          {score}/100
        </span>
      </div>
      {suggestions && suggestions.length > 0 && (
        <ul className="text-sm text-gray-600 space-y-1">
          {suggestions.map((s, i) => (
            <li key={i}>• {s}</li>
          ))}
        </ul>
      )}
    </div>
  );
}

function KeywordSuggester() {
  const [seedKeyword, setSeedKeyword] = useState('');
  const [language, setLanguage] = useState('nb');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<any[]>([]);
  const [error, setError] = useState<string | null>(null);

  const handleSuggest = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setResult([]);

    try {
      const response = await api.post('/ai/suggest-keywords', {
        seed_keyword: seedKeyword,
        language,
        count: 20,
      });
      setResult(response.data || []);
    } catch (err: any) {
      setError(err.response?.data?.message || 'En feil oppstod. Prøv igjen.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-gray-900">Finn søkeord</h2>
        <p className="text-sm text-gray-500">
          Oppdag nye søkeordmuligheter basert på et frøsøkeord
        </p>
      </div>

      <form onSubmit={handleSuggest} className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            Frøsøkeord *
          </label>
          <input
            type="text"
            value={seedKeyword}
            onChange={(e) => setSeedKeyword(e.target.value)}
            required
            placeholder="f.eks. digital markedsføring"
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Språk
          </label>
          <select
            value={language}
            onChange={(e) => setLanguage(e.target.value)}
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="nb">Norsk (Bokmål)</option>
            <option value="nn">Norsk (Nynorsk)</option>
            <option value="en">Engelsk</option>
          </select>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {error}
          </div>
        )}

        <button
          type="submit"
          disabled={loading || !seedKeyword}
          className="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {loading ? 'Søker...' : 'Finn søkeord'}
        </button>
      </form>

      {result.length > 0 && (
        <div className="mt-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Foreslåtte søkeord</h3>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Søkeord</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volum</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vanskelighet</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CPC</th>
                  <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Intensjon</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {result.map((kw, index) => (
                  <tr key={index} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{kw.keyword}</td>
                    <td className="px-4 py-3 text-sm text-gray-500">{kw.search_volume?.toLocaleString() || '-'}</td>
                    <td className="px-4 py-3 text-sm">
                      <span className={`inline-flex px-2 py-1 rounded-full text-xs font-medium ${
                        kw.difficulty <= 30 ? 'bg-green-100 text-green-800' :
                        kw.difficulty <= 60 ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                      }`}>
                        {kw.difficulty || '-'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-500">{kw.cpc ? `${kw.cpc} kr` : '-'}</td>
                    <td className="px-4 py-3 text-sm text-gray-500 capitalize">{kw.intent || '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}

function ContentGenerator() {
  const [keyword, setKeyword] = useState('');
  const [contentType, setContentType] = useState('blog_post');
  const [language, setLanguage] = useState('nb');
  const [tone, setTone] = useState('professional');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);

  const handleGenerate = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setResult(null);

    try {
      const response = await api.post('/ai/generate-content', {
        keyword,
        content_type: contentType,
        language,
        tone,
      });
      setResult(response.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'En feil oppstod. Prøv igjen.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-gray-900">Generer innhold</h2>
        <p className="text-sm text-gray-500">
          Lag SEO-optimalisert innhold med kunstig intelligens
        </p>
      </div>

      <form onSubmit={handleGenerate} className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            Målsøkeord *
          </label>
          <input
            type="text"
            value={keyword}
            onChange={(e) => setKeyword(e.target.value)}
            required
            placeholder="f.eks. beste seo tips 2024"
            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700">
              Innholdstype
            </label>
            <select
              value={contentType}
              onChange={(e) => setContentType(e.target.value)}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="blog_post">Bloggartikkel</option>
              <option value="product_description">Produktbeskrivelse</option>
              <option value="meta_description">Meta-beskrivelse</option>
              <option value="title">Tittelforslag</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700">
              Språk
            </label>
            <select
              value={language}
              onChange={(e) => setLanguage(e.target.value)}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="nb">Norsk (Bokmål)</option>
              <option value="nn">Norsk (Nynorsk)</option>
              <option value="en">Engelsk</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700">
              Tone
            </label>
            <select
              value={tone}
              onChange={(e) => setTone(e.target.value)}
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="professional">Profesjonell</option>
              <option value="casual">Uformell</option>
              <option value="friendly">Vennlig</option>
            </select>
          </div>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {error}
          </div>
        )}

        <button
          type="submit"
          disabled={loading || !keyword}
          className="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {loading ? 'Genererer...' : 'Generer innhold'}
        </button>
      </form>

      {result && (
        <div className="mt-6 space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-gray-900">Generert innhold</h3>
            <div className="flex items-center space-x-4 text-sm text-gray-500">
              <span>{result.word_count} ord</span>
              <span>~{result.estimated_reading_time} min lesetid</span>
            </div>
          </div>

          <div className="bg-gray-50 rounded-lg p-4">
            <div className="prose max-w-none">
              <pre className="whitespace-pre-wrap text-sm text-gray-800 font-sans">
                {result.content}
              </pre>
            </div>
          </div>

          <button
            onClick={() => navigator.clipboard.writeText(result.content)}
            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Kopier til utklippstavle
          </button>
        </div>
      )}
    </div>
  );
}
