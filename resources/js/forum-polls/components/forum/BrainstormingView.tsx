import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Textarea } from "../ui/textarea";
import { Badge } from "../ui/badge";
import { Avatar, AvatarImage, AvatarFallback } from "../ui/avatar";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Check, X, Plus, Edit3, Lightbulb, MessageSquare, Users, TrendingUp } from "lucide-react";

interface Suggestion {
  id: string;
  author: string;
  content: string;
  timestamp: string;
  type: 'improvement' | 'alternative' | 'addition' | 'question';
  agreeCount: number;
  disagreeCount: number;
  totalVotes: number;
  hasUserAgreed: boolean;
  hasUserDisagreed: boolean;
  subSuggestions: SubSuggestion[];
}

interface SubSuggestion {
  id: string;
  author: string;
  content: string;
  timestamp: string;
  agreeCount: number;
  disagreeCount: number;
  totalVotes: number;
  hasUserAgreed: boolean;
  hasUserDisagreed: boolean;
}

interface BrainstormingViewProps {
  topicId: string;
  topicTitle: string;
  originalProposal: string;
}

export function BrainstormingView({ topicId, topicTitle, originalProposal }: BrainstormingViewProps) {
  const [suggestions, setSuggestions] = useState<Suggestion[]>(mockSuggestions);
  const [newSuggestion, setNewSuggestion] = useState("");
  const [suggestionType, setSuggestionType] = useState<'improvement' | 'alternative' | 'addition' | 'question'>('improvement');
  const [expandedSuggestion, setExpandedSuggestion] = useState<string | null>(null);
  const [newSubSuggestion, setNewSubSuggestion] = useState("");

  const handleSubmitSuggestion = () => {
    if (newSuggestion.trim()) {
      const suggestion: Suggestion = {
        id: Date.now().toString(),
        author: "Current User",
        content: newSuggestion,
        timestamp: "Just now",
        type: suggestionType,
        agreeCount: 0,
        disagreeCount: 0,
        totalVotes: 0,
        hasUserAgreed: false,
        hasUserDisagreed: false,
        subSuggestions: []
      };
      setSuggestions([suggestion, ...suggestions]);
      setNewSuggestion("");
    }
  };

  const handleSubmitSubSuggestion = (suggestionId: string) => {
    if (newSubSuggestion.trim()) {
      setSuggestions(prev => 
        prev.map(suggestion => 
          suggestion.id === suggestionId 
            ? {
                ...suggestion,
                subSuggestions: [...suggestion.subSuggestions, {
                  id: Date.now().toString(),
                  author: "Current User",
                  content: newSubSuggestion,
                  timestamp: "Just now",
                  agreeCount: 0,
                  disagreeCount: 0,
                  totalVotes: 0,
                  hasUserAgreed: false,
                  hasUserDisagreed: false
                }]
              }
            : suggestion
        )
      );
      setNewSubSuggestion("");
      setExpandedSuggestion(null);
    }
  };

  const handleVote = (suggestionId: string, isAgree: boolean, isSubSuggestion = false, subSuggestionId?: string) => {
    setSuggestions(prev => 
      prev.map(suggestion => {
        if (suggestion.id === suggestionId) {
          if (isSubSuggestion && subSuggestionId) {
            return {
              ...suggestion,
              subSuggestions: suggestion.subSuggestions.map(sub => 
                sub.id === subSuggestionId 
                  ? {
                      ...sub,
                      agreeCount: isAgree ? 
                        (sub.hasUserAgreed ? sub.agreeCount - 1 : sub.agreeCount + 1) : 
                        (sub.hasUserDisagreed ? sub.agreeCount : sub.agreeCount),
                      disagreeCount: !isAgree ? 
                        (sub.hasUserDisagreed ? sub.disagreeCount - 1 : sub.disagreeCount + 1) : 
                        (sub.hasUserAgreed ? sub.disagreeCount : sub.disagreeCount),
                      totalVotes: isAgree ? 
                        (sub.hasUserAgreed ? sub.totalVotes - 1 : sub.totalVotes + 1) : 
                        (sub.hasUserDisagreed ? sub.totalVotes - 1 : sub.totalVotes + 1),
                      hasUserAgreed: isAgree ? !sub.hasUserAgreed : false,
                      hasUserDisagreed: !isAgree ? !sub.hasUserDisagreed : false
                    }
                  : sub
              )
            };
          } else {
            return {
              ...suggestion,
              agreeCount: isAgree ? 
                (suggestion.hasUserAgreed ? suggestion.agreeCount - 1 : suggestion.agreeCount + 1) : 
                (suggestion.hasUserDisagreed ? suggestion.agreeCount : suggestion.agreeCount),
              disagreeCount: !isAgree ? 
                (suggestion.hasUserDisagreed ? suggestion.disagreeCount - 1 : suggestion.disagreeCount + 1) : 
                (suggestion.hasUserAgreed ? suggestion.disagreeCount : suggestion.disagreeCount),
              totalVotes: isAgree ? 
                (suggestion.hasUserAgreed ? suggestion.totalVotes - 1 : suggestion.totalVotes + 1) : 
                (suggestion.hasUserDisagreed ? suggestion.totalVotes - 1 : suggestion.totalVotes + 1),
              hasUserAgreed: isAgree ? !suggestion.hasUserAgreed : false,
              hasUserDisagreed: !isAgree ? !suggestion.hasUserDisagreed : false
            };
          }
        }
        return suggestion;
      })
    );
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'improvement': return <Edit3 className="h-4 w-4" />;
      case 'alternative': return <Lightbulb className="h-4 w-4" />;
      case 'addition': return <Plus className="h-4 w-4" />;
      case 'question': return <MessageSquare className="h-4 w-4" />;
      default: return <Edit3 className="h-4 w-4" />;
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'improvement': return 'bg-blue-100 text-blue-800';
      case 'alternative': return 'bg-purple-100 text-purple-800';
      case 'addition': return 'bg-green-100 text-green-800';
      case 'question': return 'bg-yellow-100 text-yellow-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getTypeText = (type: string) => {
    switch (type) {
      case 'improvement': return 'Improvement';
      case 'alternative': return 'Alternative';
      case 'addition': return 'Addition';
      case 'question': return 'Question';
      default: return type;
    }
  };

  const getAgreementPercentage = (agreeCount: number, totalVotes: number) => {
    if (totalVotes === 0) return 0;
    return Math.round((agreeCount / totalVotes) * 100);
  };

  const getDisagreementPercentage = (disagreeCount: number, totalVotes: number) => {
    if (totalVotes === 0) return 0;
    return Math.round((disagreeCount / totalVotes) * 100);
  };

  // Sort suggestions by agreement percentage, then by total votes
  const sortedSuggestions = suggestions.sort((a, b) => {
    const aAgreementPercentage = getAgreementPercentage(a.agreeCount, a.totalVotes);
    const bAgreementPercentage = getAgreementPercentage(b.agreeCount, b.totalVotes);
    
    if (aAgreementPercentage === bAgreementPercentage) {
      return b.totalVotes - a.totalVotes;
    }
    return bAgreementPercentage - aAgreementPercentage;
  });

  return (
    <div className="space-y-6">
      <div className="space-y-4">
        <div className="flex items-center gap-2">
          <Users className="h-5 w-5 text-blue-600" />
          <h3>Collaborative Brainstorming</h3>
        </div>
        <p className="text-sm text-muted-foreground">
          Help improve this proposal by suggesting modifications, alternatives, or asking clarifying questions. 
          Vote on suggestions to show community support.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Contribute Your Ideas</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <label className="text-sm">Suggestion Type</label>
            <Select value={suggestionType} onValueChange={(value: any) => setSuggestionType(value)}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="improvement">💡 Improvement - Enhance the existing idea</SelectItem>
                <SelectItem value="alternative">🔄 Alternative - Propose a different approach</SelectItem>
                <SelectItem value="addition">➕ Addition - Add new elements</SelectItem>
                <SelectItem value="question">❓ Question - Ask for clarification</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <Textarea
            placeholder="Share your suggestion to improve this proposal..."
            value={newSuggestion}
            onChange={(e) => setNewSuggestion(e.target.value)}
            className="min-h-24"
          />
          <Button 
            onClick={handleSubmitSuggestion}
            disabled={!newSuggestion.trim()}
            className="w-full"
          >
            <Plus className="h-4 w-4 mr-2" />
            Add Suggestion
          </Button>
        </CardContent>
      </Card>

      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h4>Community Suggestions ({suggestions.length})</h4>
          <Badge variant="outline" className="flex items-center gap-1">
            <TrendingUp className="h-3 w-3" />
            Sorted by agreement
          </Badge>
        </div>
        
        {sortedSuggestions.map(suggestion => {
          const agreementPercentage = getAgreementPercentage(suggestion.agreeCount, suggestion.totalVotes);
          const disagreementPercentage = getDisagreementPercentage(suggestion.disagreeCount, suggestion.totalVotes);
          
          return (
            <Card key={suggestion.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-4">
                <div className="space-y-4">
                  <div className="flex items-start justify-between">
                    <div className="flex items-start gap-3 flex-1">
                      <Avatar className="h-8 w-8">
                        <AvatarFallback>{suggestion.author.charAt(0)}</AvatarFallback>
                      </Avatar>
                      <div className="flex-1 space-y-2">
                        <div className="flex items-center gap-2">
                          <span className="font-medium">{suggestion.author}</span>
                          <Badge className={getTypeColor(suggestion.type)} variant="outline">
                            {getTypeIcon(suggestion.type)}
                            <span className="ml-1">{getTypeText(suggestion.type)}</span>
                          </Badge>
                          <span className="text-sm text-muted-foreground">{suggestion.timestamp}</span>
                        </div>
                        <p className="text-sm">{suggestion.content}</p>
                      </div>
                    </div>
                  </div>

                  {suggestion.totalVotes > 0 && (
                    <div className="space-y-1">
                      <div className="w-full bg-gray-200 rounded-full h-1 flex overflow-hidden">
                        <div 
                          className="bg-pink-400 h-1 transition-all duration-300"
                          style={{ width: `${agreementPercentage}%` }}
                        />
                        <div 
                          className="bg-sky-400 h-1 transition-all duration-300"
                          style={{ width: `${disagreementPercentage}%` }}
                        />
                      </div>
                      <div className="flex justify-between text-xs text-muted-foreground">
                        <span className="text-pink-600">{agreementPercentage}% agree</span>
                        <span className="text-sky-600">{disagreementPercentage}% disagree</span>
                      </div>
                    </div>
                  )}

                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-1">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleVote(suggestion.id, true)}
                        className={`h-6 px-2 text-xs ${suggestion.hasUserAgreed ? 'bg-pink-100 text-pink-700 border-pink-300' : 'hover:bg-pink-50'}`}
                      >
                        <Check className="h-2.5 w-2.5 mr-1" />
                        {suggestion.agreeCount}
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleVote(suggestion.id, false)}
                        className={`h-6 px-2 text-xs ${suggestion.hasUserDisagreed ? 'bg-sky-100 text-sky-700 border-sky-300' : 'hover:bg-sky-50'}`}
                      >
                        <X className="h-2.5 w-2.5 mr-1" />
                        {suggestion.disagreeCount}
                      </Button>
                    </div>
                    
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setExpandedSuggestion(
                        expandedSuggestion === suggestion.id ? null : suggestion.id
                      )}
                    >
                      <Plus className="h-3 w-3 mr-1" />
                      Build on this ({suggestion.subSuggestions.length})
                    </Button>
                  </div>

                  {suggestion.subSuggestions.length > 0 && (
                    <div className="ml-11 space-y-3 pt-2 border-t border-gray-100">
                      {suggestion.subSuggestions.map(subSuggestion => {
                        const subAgreementPercentage = getAgreementPercentage(subSuggestion.agreeCount, subSuggestion.totalVotes);
                        const subDisagreementPercentage = getDisagreementPercentage(subSuggestion.disagreeCount, subSuggestion.totalVotes);
                        
                        return (
                          <div key={subSuggestion.id} className="space-y-2">
                            <div className="flex items-start gap-2">
                              <Avatar className="h-6 w-6">
                                <AvatarFallback className="text-xs">
                                  {subSuggestion.author.charAt(0)}
                                </AvatarFallback>
                              </Avatar>
                              <div className="flex-1">
                                <div className="flex items-center gap-2 mb-1">
                                  <span className="text-sm font-medium">{subSuggestion.author}</span>
                                  <span className="text-xs text-muted-foreground">{subSuggestion.timestamp}</span>
                                </div>
                                <p className="text-sm">{subSuggestion.content}</p>
                              </div>
                            </div>
                            

                            
                            <div className="flex items-center gap-1 ml-8">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleVote(suggestion.id, true, true, subSuggestion.id)}
                                className={`h-5 px-1.5 text-xs ${subSuggestion.hasUserAgreed ? 'bg-pink-100 text-pink-700 border-pink-300' : 'hover:bg-pink-50'}`}
                              >
                                <Check className="h-2 w-2 mr-0.5" />
                                {subSuggestion.agreeCount}
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleVote(suggestion.id, false, true, subSuggestion.id)}
                                className={`h-5 px-1.5 text-xs ${subSuggestion.hasUserDisagreed ? 'bg-sky-100 text-sky-700 border-sky-300' : 'hover:bg-sky-50'}`}
                              >
                                <X className="h-2 w-2 mr-0.5" />
                                {subSuggestion.disagreeCount}
                              </Button>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  )}

                  {expandedSuggestion === suggestion.id && (
                    <div className="ml-11 space-y-2 pt-2 border-t border-gray-100">
                      <Textarea
                        placeholder="Build on this suggestion..."
                        value={newSubSuggestion}
                        onChange={(e) => setNewSubSuggestion(e.target.value)}
                        className="min-h-16"
                      />
                      <div className="flex gap-2">
                        <Button 
                          size="sm" 
                          onClick={() => handleSubmitSubSuggestion(suggestion.id)}
                          disabled={!newSubSuggestion.trim()}
                        >
                          Add
                        </Button>
                        <Button 
                          variant="outline" 
                          size="sm" 
                          onClick={() => {
                            setExpandedSuggestion(null);
                            setNewSubSuggestion("");
                          }}
                        >
                          Cancel
                        </Button>
                      </div>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          );
        })}

        {suggestions.length === 0 && (
          <div className="text-center py-8 text-muted-foreground">
            <Lightbulb className="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p>No suggestions yet. Be the first to contribute!</p>
          </div>
        )}
      </div>
    </div>
  );
}

// Mock data for suggestions with agreement/disagreement system
const mockSuggestions: Suggestion[] = [
  {
    id: "s1",
    author: "Maria Santos",
    content: "Instead of removing parking entirely, what if we implement bike lanes during certain hours (like 7-9 AM and 5-7 PM) and allow parking during off-peak times? This could be a compromise that addresses both cycling safety and business parking needs.",
    timestamp: "2 hours ago",
    type: "alternative",
    agreeCount: 18,
    disagreeCount: 3,
    totalVotes: 21,
    hasUserAgreed: false,
    hasUserDisagreed: false,
    subSuggestions: [
      {
        id: "ss1",
        author: "Tom Wilson",
        content: "This is brilliant! We could use moveable barriers or flexible bollards to make this work. I've seen similar systems in European cities.",
        timestamp: "1 hour ago",
        agreeCount: 8,
        disagreeCount: 1,
        totalVotes: 9,
        hasUserAgreed: false,
        hasUserDisagreed: false
      },
      {
        id: "ss2",
        author: "Sarah Johnson",
        content: "Great idea! We'd need clear signage and maybe a mobile app to notify people of the schedule changes. The enforcement might be challenging though.",
        timestamp: "45 minutes ago",
        agreeCount: 6,
        disagreeCount: 2,
        totalVotes: 8,
        hasUserAgreed: false,
        hasUserDisagreed: false
      }
    ]
  },
  {
    id: "s2",
    author: "Alex Thompson",
    content: "We should also consider adding bike repair stations along the route. Many cyclists avoid longer commutes because they're worried about breakdowns. Having tools and air pumps available would make cycling more accessible.",
    timestamp: "3 hours ago",
    type: "addition",
    agreeCount: 15,
    disagreeCount: 2,
    totalVotes: 17,
    hasUserAgreed: true,
    hasUserDisagreed: false,
    subSuggestions: [
      {
        id: "ss3",
        author: "Jennifer Lee",
        content: "Yes! And maybe partner with local bike shops to sponsor them in exchange for advertising space.",
        timestamp: "2 hours ago",
        agreeCount: 7,
        disagreeCount: 0,
        totalVotes: 7,
        hasUserAgreed: false,
        hasUserDisagreed: false
      }
    ]
  },
  {
    id: "s3",
    author: "Carlos Rivera",
    content: "What's the estimated cost for this project? We should include budget details and potential funding sources (grants, city budget, community fundraising) to make this more actionable.",
    timestamp: "4 hours ago",
    type: "question",
    agreeCount: 12,
    disagreeCount: 1,
    totalVotes: 13,
    hasUserAgreed: false,
    hasUserDisagreed: false,
    subSuggestions: []
  },
  {
    id: "s4",
    author: "Lisa Park",
    content: "The proposal mentions barriers but doesn't specify the type. I suggest using planters with native plants instead of concrete barriers. This would add greenery to Main Street while providing protection for cyclists.",
    timestamp: "5 hours ago",
    type: "improvement",
    agreeCount: 22,
    disagreeCount: 1,
    totalVotes: 23,
    hasUserAgreed: false,
    hasUserDisagreed: false,
    subSuggestions: [
      {
        id: "ss4",
        author: "Mike Chen",
        content: "Love this! It would also help with stormwater management and air quality. We could plant species that require minimal maintenance.",
        timestamp: "4 hours ago",
        agreeCount: 11,
        disagreeCount: 0,
        totalVotes: 11,
        hasUserAgreed: false,
        hasUserDisagreed: false
      }
    ]
  }
];