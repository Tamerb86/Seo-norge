'use client';

import { ReactNode } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center gap-1 font-medium transition-colors',
  {
    variants: {
      variant: {
        default: 'bg-gray-100 text-gray-800',
        primary: 'bg-blue-100 text-blue-800',
        secondary: 'bg-gray-100 text-gray-600',
        success: 'bg-green-100 text-green-800',
        warning: 'bg-yellow-100 text-yellow-800',
        danger: 'bg-red-100 text-red-800',
        info: 'bg-cyan-100 text-cyan-800',
        purple: 'bg-purple-100 text-purple-800',
        outline: 'bg-transparent border border-gray-300 text-gray-700',
        'outline-primary': 'bg-transparent border border-blue-300 text-blue-700',
        'outline-success': 'bg-transparent border border-green-300 text-green-700',
        'outline-danger': 'bg-transparent border border-red-300 text-red-700',
      },
      size: {
        xs: 'text-[10px] px-1.5 py-0.5 rounded',
        sm: 'text-xs px-2 py-0.5 rounded-md',
        md: 'text-sm px-2.5 py-1 rounded-md',
        lg: 'text-sm px-3 py-1.5 rounded-lg',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'sm',
    },
  }
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof badgeVariants> {
  icon?: ReactNode;
  removable?: boolean;
  onRemove?: () => void;
}

export function Badge({
  className,
  variant,
  size,
  icon,
  removable,
  onRemove,
  children,
  ...props
}: BadgeProps) {
  return (
    <span className={cn(badgeVariants({ variant, size }), className)} {...props}>
      {icon && <span className="flex-shrink-0">{icon}</span>}
      {children}
      {removable && (
        <button
          onClick={onRemove}
          className="flex-shrink-0 ml-1 -mr-1 p-0.5 rounded hover:bg-black/10 transition-colors"
        >
          <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      )}
    </span>
  );
}

// Status Badge with dot indicator
interface StatusBadgeProps {
  status: 'online' | 'offline' | 'busy' | 'away' | 'active' | 'inactive' | 'pending';
  label?: string;
  size?: 'sm' | 'md';
  className?: string;
}

const statusConfig = {
  online: { color: 'bg-green-500', label: 'Online', textColor: 'text-green-700' },
  offline: { color: 'bg-gray-400', label: 'Offline', textColor: 'text-gray-600' },
  busy: { color: 'bg-red-500', label: 'Opptatt', textColor: 'text-red-700' },
  away: { color: 'bg-yellow-500', label: 'Borte', textColor: 'text-yellow-700' },
  active: { color: 'bg-green-500', label: 'Aktiv', textColor: 'text-green-700' },
  inactive: { color: 'bg-gray-400', label: 'Inaktiv', textColor: 'text-gray-600' },
  pending: { color: 'bg-yellow-500', label: 'Venter', textColor: 'text-yellow-700' },
};

export function StatusBadge({ status, label, size = 'sm', className }: StatusBadgeProps) {
  const config = statusConfig[status];
  const displayLabel = label || config.label;

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5',
        size === 'sm' ? 'text-xs' : 'text-sm',
        config.textColor,
        className
      )}
    >
      <span className={cn('w-2 h-2 rounded-full', config.color, status === 'online' && 'animate-pulse')} />
      {displayLabel}
    </span>
  );
}

// Ranking Badge
interface RankingBadgeProps {
  position: number;
  change?: number;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export function RankingBadge({ position, change, size = 'md', className }: RankingBadgeProps) {
  const getPositionColor = (pos: number) => {
    if (pos <= 3) return 'bg-green-100 text-green-800 border-green-200';
    if (pos <= 10) return 'bg-blue-100 text-blue-800 border-blue-200';
    if (pos <= 20) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (pos <= 50) return 'bg-orange-100 text-orange-800 border-orange-200';
    return 'bg-red-100 text-red-800 border-red-200';
  };

  const sizeClasses = {
    sm: 'text-xs px-2 py-0.5',
    md: 'text-sm px-2.5 py-1',
    lg: 'text-base px-3 py-1.5',
  };

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 font-semibold rounded-lg border',
        getPositionColor(position),
        sizeClasses[size],
        className
      )}
    >
      #{position}
      {change !== undefined && change !== 0 && (
        <span
          className={cn(
            'text-xs font-medium',
            change > 0 ? 'text-green-600' : 'text-red-600'
          )}
        >
          {change > 0 ? '↑' : '↓'}{Math.abs(change)}
        </span>
      )}
    </span>
  );
}

// Plan Badge
interface PlanBadgeProps {
  plan: 'free' | 'starter' | 'pro' | 'enterprise';
  className?: string;
}

const planConfig = {
  free: { label: 'Gratis', variant: 'secondary' as const },
  starter: { label: 'Starter', variant: 'primary' as const },
  pro: { label: 'Pro', variant: 'purple' as const },
  enterprise: { label: 'Enterprise', variant: 'success' as const },
};

export function PlanBadge({ plan, className }: PlanBadgeProps) {
  const config = planConfig[plan];
  return (
    <Badge variant={config.variant} size="sm" className={className}>
      {config.label}
    </Badge>
  );
}

// Keyword Difficulty Badge
interface DifficultyBadgeProps {
  difficulty: number; // 0-100
  showLabel?: boolean;
  className?: string;
}

export function DifficultyBadge({ difficulty, showLabel = true, className }: DifficultyBadgeProps) {
  const getConfig = (diff: number) => {
    if (diff <= 20) return { label: 'Lett', variant: 'success' as const };
    if (diff <= 40) return { label: 'Moderat', variant: 'info' as const };
    if (diff <= 60) return { label: 'Middels', variant: 'warning' as const };
    if (diff <= 80) return { label: 'Vanskelig', variant: 'danger' as const };
    return { label: 'Veldig vanskelig', variant: 'danger' as const };
  };

  const config = getConfig(difficulty);

  return (
    <Badge variant={config.variant} size="sm" className={className}>
      {difficulty}
      {showLabel && <span className="ml-1 opacity-75">({config.label})</span>}
    </Badge>
  );
}

// Tag Group
interface TagGroupProps {
  tags: string[];
  max?: number;
  variant?: VariantProps<typeof badgeVariants>['variant'];
  size?: VariantProps<typeof badgeVariants>['size'];
  onRemove?: (tag: string) => void;
  className?: string;
}

export function TagGroup({
  tags,
  max = 5,
  variant = 'default',
  size = 'sm',
  onRemove,
  className,
}: TagGroupProps) {
  const visibleTags = tags.slice(0, max);
  const hiddenCount = tags.length - max;

  return (
    <div className={cn('flex flex-wrap gap-1.5', className)}>
      {visibleTags.map((tag) => (
        <Badge
          key={tag}
          variant={variant}
          size={size}
          removable={!!onRemove}
          onRemove={() => onRemove?.(tag)}
        >
          {tag}
        </Badge>
      ))}
      {hiddenCount > 0 && (
        <Badge variant="secondary" size={size}>
          +{hiddenCount} mer
        </Badge>
      )}
    </div>
  );
}
