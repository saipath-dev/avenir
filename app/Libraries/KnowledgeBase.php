<?php

namespace App\Libraries;

class KnowledgeBase
{
    private $knowledgeBasePath;
    private $knowledgeData = [];
    private $cache;

    public function __construct()
    {
        $this->knowledgeBasePath = ROOTPATH . 'Documentation/AI';
        $this->cache = \Config\Services::cache();
        $this->loadKnowledgeBase();
    }

    /**
     * Load all JSON knowledge base files
     */
    private function loadKnowledgeBase()
    {
        // Check cache first
        $cacheKey = 'knowledge_base_data';
        $cachedData = $this->cache->get($cacheKey);
        
        if ($cachedData !== null) {
            $this->knowledgeData = $cachedData;
            return;
        }

        // Load from files if not cached
        $this->knowledgeData = [];
        $jsonFiles = glob($this->knowledgeBasePath . '/*.json');
        
        foreach ($jsonFiles as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            if ($data && isset($data['topic'])) {
                $this->knowledgeData[] = $data;
            }
        }
        
        // Cache for 1 hour
        $this->cache->save($cacheKey, $this->knowledgeData, 3600);
    }

    /**
     * Search knowledge base for relevant content based on user query
     */
    public function searchRelevantContent(string $userQuery): array
    {
        $queryWords = explode(' ', strtolower(trim($userQuery)));
        $results = [];
        
        foreach ($this->knowledgeData as $knowledge) {
            $relevanceScore = $this->calculateRelevanceScore($knowledge, $queryWords, $userQuery);
            
            if ($relevanceScore > 0) {
                $results[] = [
                    'knowledge' => $knowledge,
                    'relevance_score' => $relevanceScore,
                    'matched_keywords' => $this->getMatchedKeywords($knowledge, $queryWords),
                    'matched_patterns' => $this->getMatchedPatterns($knowledge, $userQuery)
                ];
            }
        }
        
        // Sort by relevance score (highest first)
        usort($results, function($a, $b) {
            return $b['relevance_score'] - $a['relevance_score'];
        });
        
        return array_slice($results, 0, 3); // Return top 3 most relevant
    }

    /**
     * Calculate relevance score for a knowledge item
     */
    private function calculateRelevanceScore(array $knowledge, array $queryWords, string $fullQuery): int
    {
        $score = 0;
        
        // Check intent patterns (highest priority)
        foreach ($knowledge['intent_patterns'] as $pattern) {
            $similarity = $this->calculateTextSimilarity($fullQuery, $pattern);
            if ($similarity > 0.6) {
                $score += 50; // High score for intent match
            } elseif ($similarity > 0.3) {
                $score += 25; // Medium score for partial intent match
            }
        }
        
        // Check keywords (medium priority)
        foreach ($queryWords as $word) {
            if (strlen($word) < 3) continue; // Skip short words
            
            if (in_array($word, $knowledge['keywords'])) {
                $score += 15; // Direct keyword match
            }
            
            // Check if keyword appears in topic
            if (stripos($knowledge['topic'], $word) !== false) {
                $score += 10;
            }
        }
        
        // Check workflow titles (medium priority)
        if (isset($knowledge['workflows'])) {
            foreach ($knowledge['workflows'] as $workflow) {
                foreach ($queryWords as $word) {
                    if (stripos($workflow['title'], $word) !== false) {
                        $score += 8;
                    }
                }
            }
        }
        
        // Check common questions (lower priority)
        if (isset($knowledge['common_questions'])) {
            foreach ($knowledge['common_questions'] as $qa) {
                $similarity = $this->calculateTextSimilarity($fullQuery, $qa['question']);
                if ($similarity > 0.4) {
                    $score += 20;
                }
            }
        }
        
        return $score;
    }

    /**
     * Calculate text similarity using simple word overlap
     */
    private function calculateTextSimilarity(string $text1, string $text2): float
    {
        $words1 = explode(' ', strtolower($text1));
        $words2 = explode(' ', strtolower($text2));
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        if (empty($union)) {
            return 0;
        }
        
        return count($intersection) / count($union);
    }

    /**
     * Get matched keywords for result explanation
     */
    private function getMatchedKeywords(array $knowledge, array $queryWords): array
    {
        $matched = [];
        
        foreach ($queryWords as $word) {
            if (strlen($word) >= 3 && in_array($word, $knowledge['keywords'])) {
                $matched[] = $word;
            }
        }
        
        return $matched;
    }

    /**
     * Get matched intent patterns
     */
    private function getMatchedPatterns(array $knowledge, string $query): array
    {
        $matched = [];
        
        foreach ($knowledge['intent_patterns'] as $pattern) {
            $similarity = $this->calculateTextSimilarity($query, $pattern);
            if ($similarity > 0.3) {
                $matched[] = [
                    'pattern' => $pattern,
                    'similarity' => $similarity
                ];
            }
        }
        
        return $matched;
    }

    /**
     * Get context string for AI processing
     */
    public function buildContextForAI(array $searchResults, string $userQuery): string
    {
        if (empty($searchResults)) {
            return "No specific documentation found for this query. Please provide general travel management guidance.";
        }
        
        $context = "Based on the Avenir Travel Management System knowledge base:\n\n";
        
        foreach ($searchResults as $result) {
            $knowledge = $result['knowledge'];
            
            $context .= "=== " . $knowledge['topic'] . " ===\n";
            $context .= $knowledge['description'] . "\n\n";
            
            // Add most relevant workflow
            if (isset($knowledge['workflows']) && !empty($knowledge['workflows'])) {
                $relevantWorkflow = $this->findMostRelevantWorkflow($knowledge['workflows'], $userQuery);
                if ($relevantWorkflow) {
                    $context .= "Process: " . $relevantWorkflow['title'] . "\n";
                    foreach ($relevantWorkflow['steps'] as $index => $step) {
                        $context .= ($index + 1) . ". " . $step . "\n";
                    }
                    $context .= "\n";
                }
            }
            
            // Add relevant common question if exists
            $relevantQA = $this->findRelevantQuestion($knowledge, $userQuery);
            if ($relevantQA) {
                $context .= "Q: " . $relevantQA['question'] . "\n";
                $context .= "A: " . $relevantQA['answer'] . "\n\n";
            }
        }
        
        return $context;
    }

    /**
     * Find most relevant workflow for the query
     */
    private function findMostRelevantWorkflow(array $workflows, string $query): ?array
    {
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($workflows as $workflow) {
            $score = $this->calculateTextSimilarity($query, $workflow['title']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $workflow;
            }
        }
        
        return $bestMatch ?: $workflows[0]; // Return best match or first workflow
    }

    /**
     * Find relevant Q&A for the query
     */
    private function findRelevantQuestion(array $knowledge, string $query): ?array
    {
        if (!isset($knowledge['common_questions'])) {
            return null;
        }
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($knowledge['common_questions'] as $qa) {
            $score = $this->calculateTextSimilarity($query, $qa['question']);
            if ($score > $bestScore && $score > 0.3) {
                $bestScore = $score;
                $bestMatch = $qa;
            }
        }
        
        return $bestMatch;
    }

    /**
     * Get suggested questions from all knowledge base
     */
    public function getSuggestedQuestions(): array
    {
        $suggestions = [];
        
        foreach ($this->knowledgeData as $knowledge) {
            $category = $knowledge['topic'];
            $suggestions[$category] = [];
            
            // Add some intent patterns as suggestions
            if (isset($knowledge['intent_patterns'])) {
                $patterns = array_slice($knowledge['intent_patterns'], 0, 3); // First 3 patterns
                foreach ($patterns as $pattern) {
                    $suggestions[$category][] = ucfirst($pattern) . '?';
                }
            }
            
            // Add some common questions
            if (isset($knowledge['common_questions'])) {
                $questions = array_slice($knowledge['common_questions'], 0, 2); // First 2 questions
                foreach ($questions as $qa) {
                    $suggestions[$category][] = $qa['question'];
                }
            }
        }
        
        return $suggestions;
    }

    /**
     * Get quick actions for a query result
     */
    public function getQuickActionsForResults(array $searchResults): array
    {
        $actions = [];
        
        foreach ($searchResults as $result) {
            $knowledge = $result['knowledge'];
            
            if (isset($knowledge['quick_actions'])) {
                foreach ($knowledge['quick_actions'] as $action) {
                    // Avoid duplicates
                    $exists = false;
                    foreach ($actions as $existingAction) {
                        if ($existingAction['url'] === $action['url']) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $actions[] = $action;
                    }
                }
            }
        }
        
        return array_slice($actions, 0, 4); // Limit to 4 actions
    }

    /**
     * Get all available topics
     */
    public function getAllTopics(): array
    {
        $topics = [];
        
        foreach ($this->knowledgeData as $knowledge) {
            $topics[] = [
                'topic' => $knowledge['topic'],
                'description' => $knowledge['description'],
                'keywords' => $knowledge['keywords']
            ];
        }
        
        return $topics;
    }

    /**
     * Get knowledge base statistics
     */
    public function getStatistics(): array
    {
        $totalWorkflows = 0;
        $totalQuestions = 0;
        $totalKeywords = 0;
        
        foreach ($this->knowledgeData as $knowledge) {
            if (isset($knowledge['workflows'])) {
                $totalWorkflows += count($knowledge['workflows']);
            }
            if (isset($knowledge['common_questions'])) {
                $totalQuestions += count($knowledge['common_questions']);
            }
            if (isset($knowledge['keywords'])) {
                $totalKeywords += count($knowledge['keywords']);
            }
        }
        
        return [
            'total_topics' => count($this->knowledgeData),
            'total_workflows' => $totalWorkflows,
            'total_questions' => $totalQuestions,
            'total_keywords' => $totalKeywords,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Refresh knowledge base (clear cache)
     */
    public function refreshKnowledgeBase(): bool
    {
        $this->cache->delete('knowledge_base_data');
        $this->loadKnowledgeBase();
        return true;
    }
}