/**
 * SEO Norge Design System
 * 
 * Centralized design tokens and style constants for consistent UI.
 * Based on Tailwind CSS utility classes.
 */

// ============================================
// Color Palette
// ============================================

export const colors = {
  // Primary - Norwegian Blue
  primary: {
    50: 'bg-blue-50',
    100: 'bg-blue-100',
    200: 'bg-blue-200',
    300: 'bg-blue-300',
    400: 'bg-blue-400',
    500: 'bg-blue-500', // Main
    600: 'bg-blue-600',
    700: 'bg-blue-700',
    800: 'bg-blue-800',
    900: 'bg-blue-900',
  },
  
  // Secondary - Norwegian Red
  secondary: {
    50: 'bg-red-50',
    100: 'bg-red-100',
    500: 'bg-red-500', // Main
    600: 'bg-red-600',
    700: 'bg-red-700',
  },

  // Success
  success: {
    light: 'bg-green-100 text-green-800',
    main: 'bg-green-500 text-white',
    dark: 'bg-green-700 text-white',
  },

  // Warning
  warning: {
    light: 'bg-yellow-100 text-yellow-800',
    main: 'bg-yellow-500 text-white',
    dark: 'bg-yellow-700 text-white',
  },

  // Error
  error: {
    light: 'bg-red-100 text-red-800',
    main: 'bg-red-500 text-white',
    dark: 'bg-red-700 text-white',
  },

  // Neutral
  neutral: {
    50: 'bg-gray-50',
    100: 'bg-gray-100',
    200: 'bg-gray-200',
    300: 'bg-gray-300',
    400: 'bg-gray-400',
    500: 'bg-gray-500',
    600: 'bg-gray-600',
    700: 'bg-gray-700',
    800: 'bg-gray-800',
    900: 'bg-gray-900',
  },
} as const;

// ============================================
// Typography
// ============================================

export const typography = {
  // Headings
  h1: 'text-4xl font-bold tracking-tight text-gray-900',
  h2: 'text-3xl font-bold tracking-tight text-gray-900',
  h3: 'text-2xl font-semibold text-gray-900',
  h4: 'text-xl font-semibold text-gray-900',
  h5: 'text-lg font-medium text-gray-900',
  h6: 'text-base font-medium text-gray-900',

  // Body
  body: 'text-base text-gray-700',
  bodySmall: 'text-sm text-gray-600',
  bodyLarge: 'text-lg text-gray-700',

  // Labels
  label: 'text-sm font-medium text-gray-700',
  labelSmall: 'text-xs font-medium text-gray-600',

  // Helper text
  helper: 'text-sm text-gray-500',
  error: 'text-sm text-red-600',
  success: 'text-sm text-green-600',

  // Links
  link: 'text-blue-600 hover:text-blue-800 underline',
  linkSubtle: 'text-gray-600 hover:text-gray-900',
} as const;

// ============================================
// Spacing
// ============================================

export const spacing = {
  // Page
  pageX: 'px-4 sm:px-6 lg:px-8',
  pageY: 'py-6 sm:py-8 lg:py-10',
  page: 'px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10',

  // Section
  sectionY: 'py-8 sm:py-12',
  sectionX: 'px-4 sm:px-6',

  // Card
  cardPadding: 'p-4 sm:p-6',
  cardPaddingSmall: 'p-3 sm:p-4',
  cardPaddingLarge: 'p-6 sm:p-8',

  // Stack (vertical spacing)
  stackXs: 'space-y-1',
  stackSm: 'space-y-2',
  stackMd: 'space-y-4',
  stackLg: 'space-y-6',
  stackXl: 'space-y-8',

  // Inline (horizontal spacing)
  inlineXs: 'space-x-1',
  inlineSm: 'space-x-2',
  inlineMd: 'space-x-4',
  inlineLg: 'space-x-6',
} as const;

// ============================================
// Shadows
// ============================================

export const shadows = {
  none: 'shadow-none',
  sm: 'shadow-sm',
  md: 'shadow-md',
  lg: 'shadow-lg',
  xl: 'shadow-xl',
  inner: 'shadow-inner',
  card: 'shadow-sm hover:shadow-md transition-shadow',
  dropdown: 'shadow-lg ring-1 ring-black ring-opacity-5',
  modal: 'shadow-xl',
} as const;

// ============================================
// Border Radius
// ============================================

export const radius = {
  none: 'rounded-none',
  sm: 'rounded-sm',
  md: 'rounded-md',
  lg: 'rounded-lg',
  xl: 'rounded-xl',
  '2xl': 'rounded-2xl',
  full: 'rounded-full',
  button: 'rounded-lg',
  card: 'rounded-xl',
  input: 'rounded-lg',
  badge: 'rounded-full',
} as const;

// ============================================
// Borders
// ============================================

export const borders = {
  default: 'border border-gray-200',
  light: 'border border-gray-100',
  dark: 'border border-gray-300',
  primary: 'border border-blue-500',
  error: 'border border-red-500',
  success: 'border border-green-500',
  focus: 'focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50',
} as const;

// ============================================
// Transitions
// ============================================

export const transitions = {
  default: 'transition-all duration-200 ease-in-out',
  fast: 'transition-all duration-150 ease-in-out',
  slow: 'transition-all duration-300 ease-in-out',
  colors: 'transition-colors duration-200',
  transform: 'transition-transform duration-200',
  opacity: 'transition-opacity duration-200',
} as const;

// ============================================
// Button Variants
// ============================================

export const buttonVariants = {
  // Primary
  primary: `
    bg-blue-600 text-white 
    hover:bg-blue-700 
    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed
  `,
  
  // Secondary
  secondary: `
    bg-gray-100 text-gray-900 
    hover:bg-gray-200 
    focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed
  `,
  
  // Outline
  outline: `
    border border-gray-300 bg-white text-gray-700 
    hover:bg-gray-50 
    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed
  `,
  
  // Ghost
  ghost: `
    text-gray-700 
    hover:bg-gray-100 
    focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed
  `,
  
  // Danger
  danger: `
    bg-red-600 text-white 
    hover:bg-red-700 
    focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed
  `,
  
  // Success
  success: `
    bg-green-600 text-white 
    hover:bg-green-700 
    focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed
  `,
} as const;

// ============================================
// Button Sizes
// ============================================

export const buttonSizes = {
  xs: 'px-2 py-1 text-xs',
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-5 py-2.5 text-base',
  xl: 'px-6 py-3 text-lg',
} as const;

// ============================================
// Input Variants
// ============================================

export const inputVariants = {
  default: `
    block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5
    text-gray-900 placeholder-gray-500
    focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50
    disabled:bg-gray-100 disabled:cursor-not-allowed
  `,
  error: `
    block w-full rounded-lg border border-red-500 bg-white px-4 py-2.5
    text-gray-900 placeholder-gray-500
    focus:border-red-500 focus:ring-2 focus:ring-red-500 focus:ring-opacity-50
  `,
  success: `
    block w-full rounded-lg border border-green-500 bg-white px-4 py-2.5
    text-gray-900 placeholder-gray-500
    focus:border-green-500 focus:ring-2 focus:ring-green-500 focus:ring-opacity-50
  `,
} as const;

// ============================================
// Card Variants
// ============================================

export const cardVariants = {
  default: `
    bg-white rounded-xl border border-gray-200 shadow-sm
    hover:shadow-md transition-shadow
  `,
  elevated: `
    bg-white rounded-xl shadow-md
    hover:shadow-lg transition-shadow
  `,
  outlined: `
    bg-white rounded-xl border-2 border-gray-200
  `,
  flat: `
    bg-gray-50 rounded-xl
  `,
  interactive: `
    bg-white rounded-xl border border-gray-200 shadow-sm
    hover:shadow-md hover:border-blue-300 transition-all cursor-pointer
  `,
} as const;

// ============================================
// Badge Variants
// ============================================

export const badgeVariants = {
  default: 'bg-gray-100 text-gray-800',
  primary: 'bg-blue-100 text-blue-800',
  success: 'bg-green-100 text-green-800',
  warning: 'bg-yellow-100 text-yellow-800',
  error: 'bg-red-100 text-red-800',
  info: 'bg-cyan-100 text-cyan-800',
} as const;

// ============================================
// Layout
// ============================================

export const layout = {
  // Container
  container: 'max-w-7xl mx-auto',
  containerSm: 'max-w-3xl mx-auto',
  containerLg: 'max-w-screen-2xl mx-auto',

  // Grid
  grid2: 'grid grid-cols-1 md:grid-cols-2 gap-6',
  grid3: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6',
  grid4: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6',

  // Flex
  flexCenter: 'flex items-center justify-center',
  flexBetween: 'flex items-center justify-between',
  flexStart: 'flex items-center justify-start',
  flexEnd: 'flex items-center justify-end',
  flexCol: 'flex flex-col',
} as const;

// ============================================
// Z-Index
// ============================================

export const zIndex = {
  dropdown: 'z-10',
  sticky: 'z-20',
  fixed: 'z-30',
  modalBackdrop: 'z-40',
  modal: 'z-50',
  popover: 'z-60',
  tooltip: 'z-70',
} as const;

// ============================================
// Breakpoints (for reference)
// ============================================

export const breakpoints = {
  sm: '640px',
  md: '768px',
  lg: '1024px',
  xl: '1280px',
  '2xl': '1536px',
} as const;

// ============================================
// Export all
// ============================================

export const designSystem = {
  colors,
  typography,
  spacing,
  shadows,
  radius,
  borders,
  transitions,
  buttonVariants,
  buttonSizes,
  inputVariants,
  cardVariants,
  badgeVariants,
  layout,
  zIndex,
  breakpoints,
} as const;

export default designSystem;
