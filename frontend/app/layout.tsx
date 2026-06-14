import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'
import { Providers } from './providers'

const inter = Inter({ 
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-inter',
})

export const metadata: Metadata = {
  title: {
    default: 'SEO Norge - AI-drevet SEO-verktøy for Norge',
    template: '%s | SEO Norge',
  },
  description: 'Norges første AI-drevne SEO-verktøy. Spor rangeringer, finn søkeord, analyser konkurrenter og optimaliser innholdet ditt med kunstig intelligens.',
  keywords: [
    'SEO',
    'søkemotoroptimalisering',
    'Norge',
    'rangeringssporing',
    'søkeordanalyse',
    'konkurrentanalyse',
    'AI SEO',
    'digital markedsføring',
  ],
  authors: [{ name: 'SEO Norge' }],
  creator: 'SEO Norge',
  publisher: 'SEO Norge',
  formatDetection: {
    email: false,
    address: false,
    telephone: false,
  },
  metadataBase: new URL(process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000'),
  alternates: {
    canonical: '/',
    languages: {
      'nb-NO': '/nb',
      'nn-NO': '/nn',
      'en': '/en',
    },
  },
  openGraph: {
    type: 'website',
    locale: 'nb_NO',
    url: '/',
    title: 'SEO Norge - AI-drevet SEO-verktøy for Norge',
    description: 'Norges første AI-drevne SEO-verktøy. Spor rangeringer, finn søkeord, analyser konkurrenter og optimaliser innholdet ditt.',
    siteName: 'SEO Norge',
    images: [
      {
        url: '/images/og-image.png',
        width: 1200,
        height: 630,
        alt: 'SEO Norge',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'SEO Norge - AI-drevet SEO-verktøy',
    description: 'Norges første AI-drevne SEO-verktøy for rangeringssporing og innholdsoptimalisering.',
    images: ['/images/og-image.png'],
  },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      'max-video-preview': -1,
      'max-image-preview': 'large',
      'max-snippet': -1,
    },
  },
  verification: {
    google: 'your-google-verification-code',
  },
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="nb" className={inter.variable} suppressHydrationWarning>
      <head>
        <link rel="icon" href="/favicon.ico" sizes="any" />
        <link rel="icon" href="/icon.svg" type="image/svg+xml" />
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
        <link rel="manifest" href="/manifest.json" />
        <meta name="theme-color" content="#2563eb" />
      </head>
      <body className={`${inter.className} antialiased`}>
        <Providers>
          {children}
        </Providers>
      </body>
    </html>
  )
}
