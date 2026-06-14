<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Domain;
use App\Models\Keyword;
use App\Models\Competitor;

class OnboardingController extends Controller
{
    /**
     * Get onboarding progress for the current user
     */
    public function getProgress(Request $request)
    {
        $user = $request->user();
        
        // Check each onboarding step
        $progress = [
            'profile' => $this->isProfileComplete($user),
            'domain' => $this->hasDomain($user),
            'keywords' => $this->hasKeywords($user),
            'competitor' => $this->hasCompetitor($user),
            'ai' => $this->hasUsedAi($user),
        ];
        
        return response()->json($progress);
    }
    
    /**
     * Complete the onboarding process
     */
    public function complete(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:100',
            'domain' => 'nullable|string|max:255',
            'domain_name' => 'nullable|string|max:255',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:255',
            'competitors' => 'nullable|array',
            'competitors.*' => 'string|max:255',
            'goals' => 'nullable|array',
            'goals.*' => 'string|max:100',
            'experience' => 'nullable|string|max:50',
        ]);
        
        // Update user profile
        $user->update([
            'full_name' => $validated['full_name'] ?? $user->full_name,
            'company' => $validated['company'] ?? $user->company,
            'role' => $validated['role'] ?? $user->role,
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);
        
        // Create domain if provided
        $domain = null;
        if (!empty($validated['domain'])) {
            $domainUrl = $this->cleanDomain($validated['domain']);
            
            $domain = Domain::create([
                'user_id' => $user->id,
                'domain' => $domainUrl,
                'name' => $validated['domain_name'] ?? $domainUrl,
                'is_active' => true,
            ]);
            
            // Add keywords if provided
            if (!empty($validated['keywords']) && $domain) {
                foreach ($validated['keywords'] as $keyword) {
                    $keyword = trim($keyword);
                    if (!empty($keyword)) {
                        Keyword::create([
                            'domain_id' => $domain->id,
                            'keyword' => $keyword,
                            'country' => 'no',
                            'language' => 'no',
                            'is_active' => true,
                        ]);
                    }
                }
            }
            
            // Add competitors if provided
            if (!empty($validated['competitors']) && $domain) {
                foreach ($validated['competitors'] as $competitor) {
                    $competitorUrl = $this->cleanDomain(trim($competitor));
                    if (!empty($competitorUrl)) {
                        Competitor::create([
                            'domain_id' => $domain->id,
                            'competitor_domain' => $competitorUrl,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
        
        // Save goals and experience
        if (!empty($validated['goals']) || !empty($validated['experience'])) {
            $user->update([
                'goals' => json_encode($validated['goals'] ?? []),
                'seo_experience' => $validated['experience'] ?? null,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Onboarding fullført!',
            'domain' => $domain,
        ]);
    }
    
    /**
     * Save user goals
     */
    public function saveGoals(Request $request)
    {
        $validated = $request->validate([
            'goals' => 'required|array',
            'goals.*' => 'string|max:100',
            'experience' => 'nullable|string|max:50',
        ]);
        
        $user = $request->user();
        $user->update([
            'goals' => json_encode($validated['goals']),
            'seo_experience' => $validated['experience'] ?? null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Mål lagret!',
        ]);
    }
    
    /**
     * Dismiss the onboarding checklist
     */
    public function dismissChecklist(Request $request)
    {
        $user = $request->user();
        $user->update([
            'checklist_dismissed' => true,
            'checklist_dismissed_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
        ]);
    }
    
    /**
     * Get onboarding tips based on user's current state
     */
    public function getTips(Request $request)
    {
        $user = $request->user();
        $tips = [];
        
        // Check what the user is missing and provide relevant tips
        if (!$this->hasDomain($user)) {
            $tips[] = [
                'id' => 'add_domain',
                'priority' => 1,
                'icon' => '🌐',
                'title' => 'Legg til ditt første nettsted',
                'description' => 'Start med å legge til nettstedet du vil optimalisere.',
                'action' => '/dashboard/domains',
                'action_label' => 'Legg til nettsted',
            ];
        } elseif (!$this->hasKeywords($user)) {
            $tips[] = [
                'id' => 'add_keywords',
                'priority' => 1,
                'icon' => '🔍',
                'title' => 'Legg til søkeord',
                'description' => 'Legg til søkeord for å begynne å spore rangeringene dine.',
                'action' => '/dashboard/domains',
                'action_label' => 'Legg til søkeord',
            ];
        } elseif (!$this->hasCompetitor($user)) {
            $tips[] = [
                'id' => 'add_competitor',
                'priority' => 2,
                'icon' => '👥',
                'title' => 'Analyser konkurrentene',
                'description' => 'Legg til konkurrenter for å se hva de gjør riktig.',
                'action' => '/dashboard/domains',
                'action_label' => 'Legg til konkurrent',
            ];
        }
        
        if (!$this->hasUsedAi($user)) {
            $tips[] = [
                'id' => 'try_ai',
                'priority' => 3,
                'icon' => '🤖',
                'title' => 'Prøv AI-verktøyene',
                'description' => 'La AI analysere innholdet ditt og gi deg forbedringsforslag.',
                'action' => '/dashboard/ai',
                'action_label' => 'Prøv AI',
            ];
        }
        
        // Sort by priority
        usort($tips, fn($a, $b) => $a['priority'] <=> $b['priority']);
        
        return response()->json([
            'tips' => $tips,
            'show_checklist' => !$user->checklist_dismissed,
        ]);
    }
    
    /**
     * Helper: Check if user profile is complete
     */
    private function isProfileComplete(User $user): bool
    {
        return !empty($user->full_name);
    }
    
    /**
     * Helper: Check if user has at least one domain
     */
    private function hasDomain(User $user): bool
    {
        return Domain::where('user_id', $user->id)->exists();
    }
    
    /**
     * Helper: Check if user has at least 5 keywords
     */
    private function hasKeywords(User $user): bool
    {
        $domainIds = Domain::where('user_id', $user->id)->pluck('id');
        return Keyword::whereIn('domain_id', $domainIds)->count() >= 5;
    }
    
    /**
     * Helper: Check if user has at least one competitor
     */
    private function hasCompetitor(User $user): bool
    {
        $domainIds = Domain::where('user_id', $user->id)->pluck('id');
        return Competitor::whereIn('domain_id', $domainIds)->exists();
    }
    
    /**
     * Helper: Check if user has used AI tools
     */
    private function hasUsedAi(User $user): bool
    {
        return $user->ai_analyses_count > 0 || 
               \App\Models\AiAnalysis::where('user_id', $user->id)->exists();
    }
    
    /**
     * Helper: Clean domain URL
     */
    private function cleanDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = preg_replace('/\/.*$/', '', $domain);
        return $domain;
    }
}
