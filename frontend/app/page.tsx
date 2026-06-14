import Link from 'next/link'
import { 
  Search, 
  TrendingUp, 
  Users, 
  Zap, 
  BarChart3, 
  Globe,
  CheckCircle,
  ArrowRight
} from 'lucide-react'

export default function HomePage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900">
      {/* Navigation */}
      <nav className="fixed top-0 w-full z-50 glass border-b border-slate-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                <Search className="w-5 h-5 text-white" />
              </div>
              <span className="font-bold text-xl">SEO Norge</span>
            </div>
            <div className="hidden md:flex items-center gap-8">
              <Link href="#features" className="text-slate-600 hover:text-primary-600 transition">
                Funksjoner
              </Link>
              <Link href="#pricing" className="text-slate-600 hover:text-primary-600 transition">
                Priser
              </Link>
              <Link href="/login" className="text-slate-600 hover:text-primary-600 transition">
                Logg inn
              </Link>
              <Link href="/register" className="btn-primary">
                Kom i gang gratis
              </Link>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="pt-32 pb-20 px-4">
        <div className="max-w-7xl mx-auto text-center">
          <div className="inline-flex items-center gap-2 bg-primary-50 dark:bg-primary-950 text-primary-600 px-4 py-2 rounded-full text-sm font-medium mb-8">
            <Zap className="w-4 h-4" />
            Norges første AI-drevne SEO-verktøy
          </div>
          
          <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold text-slate-900 dark:text-white mb-6">
            Dominer søkeresultatene
            <span className="block gradient-text">med kunstig intelligens</span>
          </h1>
          
          <p className="text-xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto mb-10">
            Spor rangeringer, finn lønnsomme søkeord, analyser konkurrenter og optimaliser 
            innholdet ditt – alt på norsk, alt på ett sted.
          </p>
          
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link href="/register" className="btn-primary text-lg px-8 py-4">
              Start gratis prøveperiode
              <ArrowRight className="w-5 h-5 ml-2" />
            </Link>
            <Link href="#demo" className="btn-outline text-lg px-8 py-4">
              Se demo
            </Link>
          </div>
          
          <p className="text-sm text-slate-500 mt-4">
            Ingen kredittkort nødvendig • 14 dagers gratis prøveperiode
          </p>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-20 px-4 bg-slate-50 dark:bg-slate-900/50">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-slate-900 dark:text-white mb-4">
              Alt du trenger for å lykkes med SEO
            </h2>
            <p className="text-xl text-slate-600 dark:text-slate-400">
              Kraftige verktøy designet spesielt for det norske markedet
            </p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {/* Feature 1 */}
            <div className="card p-6 hover:shadow-lg transition">
              <div className="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mb-4">
                <TrendingUp className="w-6 h-6 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold mb-2">Rangeringssporing</h3>
              <p className="text-slate-600 dark:text-slate-400">
                Spor søkeordene dine daglig på Google.no. Se historikk, trender og få varsler ved endringer.
              </p>
            </div>
            
            {/* Feature 2 */}
            <div className="card p-6 hover:shadow-lg transition">
              <div className="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mb-4">
                <Search className="w-6 h-6 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold mb-2">Søkeordanalyse</h3>
              <p className="text-slate-600 dark:text-slate-400">
                Finn lønnsomme søkeord med norsk søkevolum, konkurranse og CPC-data.
              </p>
            </div>
            
            {/* Feature 3 */}
            <div className="card p-6 hover:shadow-lg transition">
              <div className="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mb-4">
                <Users className="w-6 h-6 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold mb-2">Konkurrentanalyse</h3>
              <p className="text-slate-600 dark:text-slate-400">
                Se hvilke søkeord konkurrentene dine rangerer for og finn hull i strategien deres.
              </p>
            </div>
            
            {/* Feature 4 */}
            <div className="card p-6 hover:shadow-lg transition">
              <div className="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mb-4">
                <Zap className="w-6 h-6 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold mb-2">AI-innholdsanalyse</h3>
              <p className="text-slate-600 dark:text-slate-400">
                La kunstig intelligens analysere innholdet ditt og gi konkrete forbedringsforslag.
              </p>
            </div>
            
            {/* Feature 5 */}
            <div className="card p-6 hover:shadow-lg transition">
              <div className="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mb-4">
                <Globe className="w-6 h-6 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold mb-2">Lokal SEO</h3>
              <p className="text-slate-600 dark:text-slate-400">
                Optimaliser for lokale søk med Google Business Profile-integrasjon og norske kataloger.
              </p>
            </div>
            
            {/* Feature 6 */}
            <div className="card p-6 hover:shadow-lg transition">
              <div className="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center mb-4">
                <BarChart3 className="w-6 h-6 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold mb-2">Rapporter</h3>
              <p className="text-slate-600 dark:text-slate-400">
                Automatiske rapporter på e-post. Perfekt for byråer og bedrifter.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section id="pricing" className="py-20 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-slate-900 dark:text-white mb-4">
              Enkle og transparente priser
            </h2>
            <p className="text-xl text-slate-600 dark:text-slate-400">
              Velg planen som passer for deg. Oppgrader eller nedgrader når som helst.
            </p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {/* Free Plan */}
            <div className="card p-6">
              <h3 className="text-lg font-semibold mb-2">Gratis</h3>
              <div className="mb-4">
                <span className="text-4xl font-bold">0 kr</span>
                <span className="text-slate-500">/mnd</span>
              </div>
              <ul className="space-y-3 mb-6">
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>1 nettsted</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>10 søkeord</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>10 AI-analyser/mnd</span>
                </li>
              </ul>
              <Link href="/register" className="btn-outline w-full">
                Kom i gang
              </Link>
            </div>
            
            {/* Starter Plan */}
            <div className="card p-6">
              <h3 className="text-lg font-semibold mb-2">Starter</h3>
              <div className="mb-4">
                <span className="text-4xl font-bold">299 kr</span>
                <span className="text-slate-500">/mnd</span>
              </div>
              <ul className="space-y-3 mb-6">
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>3 nettsteder</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>100 søkeord</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>100 AI-analyser/mnd</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>E-postvarsler</span>
                </li>
              </ul>
              <Link href="/register?plan=starter" className="btn-outline w-full">
                Start prøveperiode
              </Link>
            </div>
            
            {/* Professional Plan */}
            <div className="card p-6 border-primary-500 border-2 relative">
              <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary-600 text-white px-3 py-1 rounded-full text-sm">
                Mest populær
              </div>
              <h3 className="text-lg font-semibold mb-2">Profesjonell</h3>
              <div className="mb-4">
                <span className="text-4xl font-bold">799 kr</span>
                <span className="text-slate-500">/mnd</span>
              </div>
              <ul className="space-y-3 mb-6">
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>10 nettsteder</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>500 søkeord</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>500 AI-analyser/mnd</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>Konkurrentanalyse</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>API-tilgang</span>
                </li>
              </ul>
              <Link href="/register?plan=professional" className="btn-primary w-full">
                Start prøveperiode
              </Link>
            </div>
            
            {/* Agency Plan */}
            <div className="card p-6">
              <h3 className="text-lg font-semibold mb-2">Byrå</h3>
              <div className="mb-4">
                <span className="text-4xl font-bold">1 999 kr</span>
                <span className="text-slate-500">/mnd</span>
              </div>
              <ul className="space-y-3 mb-6">
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>Ubegrenset nettsteder</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>Ubegrenset søkeord</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>Ubegrenset AI-analyser</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>White-label rapporter</span>
                </li>
                <li className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success-500" />
                  <span>Prioritert support</span>
                </li>
              </ul>
              <Link href="/register?plan=agency" className="btn-outline w-full">
                Kontakt oss
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 px-4 bg-primary-600">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-4xl font-bold text-white mb-4">
            Klar til å dominere søkeresultatene?
          </h2>
          <p className="text-xl text-primary-100 mb-8">
            Bli med over 500 norske bedrifter som allerede bruker SEO Norge.
          </p>
          <Link href="/register" className="inline-flex items-center gap-2 bg-white text-primary-600 px-8 py-4 rounded-lg font-semibold hover:bg-primary-50 transition">
            Start gratis i dag
            <ArrowRight className="w-5 h-5" />
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="py-12 px-4 bg-slate-900 text-slate-400">
        <div className="max-w-7xl mx-auto">
          <div className="grid md:grid-cols-4 gap-8 mb-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                  <Search className="w-5 h-5 text-white" />
                </div>
                <span className="font-bold text-xl text-white">SEO Norge</span>
              </div>
              <p className="text-sm">
                Norges første AI-drevne SEO-verktøy. Bygget for det norske markedet.
              </p>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Produkt</h4>
              <ul className="space-y-2 text-sm">
                <li><Link href="#features" className="hover:text-white transition">Funksjoner</Link></li>
                <li><Link href="#pricing" className="hover:text-white transition">Priser</Link></li>
                <li><Link href="/api" className="hover:text-white transition">API</Link></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Selskap</h4>
              <ul className="space-y-2 text-sm">
                <li><Link href="/about" className="hover:text-white transition">Om oss</Link></li>
                <li><Link href="/blog" className="hover:text-white transition">Blogg</Link></li>
                <li><Link href="/contact" className="hover:text-white transition">Kontakt</Link></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Juridisk</h4>
              <ul className="space-y-2 text-sm">
                <li><Link href="/privacy" className="hover:text-white transition">Personvern</Link></li>
                <li><Link href="/terms" className="hover:text-white transition">Vilkår</Link></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-slate-800 pt-8 text-sm text-center">
            <p>© {new Date().getFullYear()} SEO Norge. Alle rettigheter reservert.</p>
          </div>
        </div>
      </footer>
    </div>
  )
}
