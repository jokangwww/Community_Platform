import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../ui/tabs";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { 
  BarChart3, 
  FileText, 
  TrendingUp, 
  Users, 
  Bookmark,
  MessageSquare,
  Share2,
  Clock,
  CheckCircle2,
  Calendar,
  Trophy,
  Flame
} from "lucide-react";

interface Poll {
  id: string;
  title: string;
  category: string;
  expiryDate: string;
  totalVotes: number;
  hasVoted: boolean;
  isExpired: boolean;
}

interface Petition {
  id: string;
  title: string;
  supportCount: number;
  hasSupported: boolean;
  createdAt: string;
}

interface CampusVoice {
  id: string;
  nickname: string;
  totalVotes: number;
  totalInteractions: number;
  level: string;
  avatar: string;
}

interface CampusConcern {
  id: string;
  title: string;
  type: 'poll' | 'petition';
  currentParticipants: number;
  weekOverWeekIncrease: number;
  category: string;
}

interface UserDashboardPanelProps {
  userId: string;
  createdPolls: Poll[];
  participatedPolls: Poll[];
  bookmarkedPolls: Poll[];
  createdPetitions: Petition[];
  supportedPetitions: Petition[];
  bookmarkedPetitions: Petition[];
  campusVoices: CampusVoice[];
  campusConcerns: CampusConcern[];
  onViewPoll: (pollId: string) => void;
  onViewPetition: (petitionId: string) => void;
  onBookmark: (type: 'poll' | 'petition', id: string) => void;
  onShare: (type: 'poll' | 'petition', id: string) => void;
}

export function UserDashboardPanel({
  userId,
  createdPolls,
  participatedPolls,
  bookmarkedPolls,
  createdPetitions,
  supportedPetitions,
  bookmarkedPetitions,
  campusVoices,
  campusConcerns,
  onViewPoll,
  onViewPetition,
  onBookmark,
  onShare
}: UserDashboardPanelProps) {
  const [activeTab, setActiveTab] = useState<'polls' | 'petitions'>('polls');

  const getCategoryColor = (category: string) => {
    const colors: { [key: string]: string } = {
      "campus-life": "bg-blue-100 text-blue-800",
      "facilities": "bg-green-100 text-green-800",
      "academic": "bg-purple-100 text-purple-800",
      "events": "bg-orange-100 text-orange-800",
      "sports": "bg-red-100 text-red-800",
      "student-services": "bg-cyan-100 text-cyan-800"
    };
    return colors[category] || "bg-gray-100 text-gray-800";
  };

  const formatCategoryName = (category: string) => {
    return category.split('-').map(word => 
      word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
  };

  return (
    <div className="space-y-6">
      <div>
        <h1>My Participation Dashboard</h1>
        <p className="text-muted-foreground">
          Track your polls, petitions, and campus engagement
        </p>
      </div>

      {/* Campus Highlights */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Top 3 Campus Voices */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Trophy className="h-5 w-5 text-yellow-600" />
              Top 3 Campus Voices
            </CardTitle>
            <p className="text-sm text-muted-foreground">
              Most active and engaged community members
            </p>
          </CardHeader>
          <CardContent className="space-y-4">
            {campusVoices.slice(0, 3).map((voice, index) => (
              <div 
                key={voice.id} 
                className="flex items-center gap-4 p-3 rounded-lg border bg-gradient-to-r from-yellow-50 to-orange-50"
              >
                <div className="flex items-center gap-3 flex-1">
                  <div className="relative">
                    <Avatar className="h-12 w-12 border-2 border-yellow-400">
                      <AvatarFallback>
                        {voice.nickname.charAt(0).toUpperCase()}
                      </AvatarFallback>
                    </Avatar>
                    <div className="absolute -top-1 -right-1 h-6 w-6 rounded-full bg-yellow-500 flex items-center justify-center text-white text-xs font-bold">
                      {index + 1}
                    </div>
                  </div>
                  <div className="flex-1">
                    <p className="font-medium">{voice.nickname}</p>
                    <Badge variant="secondary" className="text-xs">
                      {voice.level}
                    </Badge>
                  </div>
                </div>
                <div className="text-right">
                  <div className="font-semibold text-lg text-yellow-700">
                    {voice.totalVotes + voice.totalInteractions}
                  </div>
                  <p className="text-xs text-muted-foreground">
                    interactions
                  </p>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>

        {/* Top 3 Campus Concerns */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Flame className="h-5 w-5 text-red-600" />
              Top 3 Campus Concerns
            </CardTitle>
            <p className="text-sm text-muted-foreground">
              Trending issues with rising participation
            </p>
          </CardHeader>
          <CardContent className="space-y-3">
            {campusConcerns.slice(0, 3).map((concern, index) => (
              <div 
                key={concern.id}
                className="p-3 rounded-lg border hover:shadow-md transition-shadow cursor-pointer"
                onClick={() => concern.type === 'poll' ? onViewPoll(concern.id) : onViewPetition(concern.id)}
              >
                <div className="flex items-start justify-between mb-2">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      {concern.type === 'poll' ? (
                        <BarChart3 className="h-4 w-4 text-blue-600" />
                      ) : (
                        <FileText className="h-4 w-4 text-green-600" />
                      )}
                      <span className="text-sm font-medium line-clamp-1">
                        {concern.title}
                      </span>
                    </div>
                    <Badge className={getCategoryColor(concern.category)} variant="secondary">
                      {formatCategoryName(concern.category)}
                    </Badge>
                  </div>
                  <Badge variant="secondary" className="bg-red-100 text-red-700 shrink-0">
                    +{concern.weekOverWeekIncrease}%
                  </Badge>
                </div>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-1 text-sm text-muted-foreground">
                    <Users className="h-3 w-3" />
                    <span>{concern.currentParticipants} participants</span>
                  </div>
                  <div className="flex gap-1">
                    <Button size="sm" variant="ghost" onClick={(e) => {
                      e.stopPropagation();
                      onShare(concern.type, concern.id);
                    }}>
                      <Share2 className="h-3 w-3" />
                    </Button>
                  </div>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      {/* Participation Tabs */}
      <Card>
        <CardHeader>
          <Tabs value={activeTab} onValueChange={(v: any) => setActiveTab(v)}>
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="polls">
                <BarChart3 className="h-4 w-4 mr-2" />
                My Polls
              </TabsTrigger>
              <TabsTrigger value="petitions">
                <FileText className="h-4 w-4 mr-2" />
                My Petitions
              </TabsTrigger>
            </TabsList>
          </Tabs>
        </CardHeader>
        <CardContent>
          {activeTab === 'polls' && (
            <Tabs defaultValue="created" className="space-y-4">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="created">
                  Created ({createdPolls.length})
                </TabsTrigger>
                <TabsTrigger value="participated">
                  Participated ({participatedPolls.length})
                </TabsTrigger>
                <TabsTrigger value="bookmarked">
                  <Bookmark className="h-3 w-3 mr-1" />
                  Saved ({bookmarkedPolls.length})
                </TabsTrigger>
              </TabsList>

              <TabsContent value="created" className="space-y-3">
                {createdPolls.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <BarChart3 className="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>You haven't created any polls yet</p>
                  </div>
                ) : (
                  createdPolls.map(poll => (
                    <div 
                      key={poll.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex-1">
                        <h4 className="mb-2">{poll.title}</h4>
                        <div className="flex items-center gap-3 text-sm">
                          <Badge className={getCategoryColor(poll.category)}>
                            {formatCategoryName(poll.category)}
                          </Badge>
                          <div className="flex items-center gap-1 text-muted-foreground">
                            <Users className="h-3 w-3" />
                            <span>{poll.totalVotes} votes</span>
                          </div>
                          <div className="flex items-center gap-1 text-muted-foreground">
                            <Clock className="h-3 w-3" />
                            <span>{poll.isExpired ? 'Expired' : `Expires ${poll.expiryDate}`}</span>
                          </div>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => onBookmark('poll', poll.id)}
                        >
                          <Bookmark className="h-4 w-4" />
                        </Button>
                        <Button
                          size="sm"
                          onClick={() => onViewPoll(poll.id)}
                        >
                          View Results
                        </Button>
                      </div>
                    </div>
                  ))
                )}
              </TabsContent>

              <TabsContent value="participated" className="space-y-3">
                {participatedPolls.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <CheckCircle2 className="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>You haven't voted in any polls yet</p>
                  </div>
                ) : (
                  participatedPolls.map(poll => (
                    <div 
                      key={poll.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <h4>{poll.title}</h4>
                          {poll.hasVoted && (
                            <Badge variant="secondary" className="bg-green-100 text-green-700">
                              <CheckCircle2 className="h-3 w-3 mr-1" />
                              Voted
                            </Badge>
                          )}
                        </div>
                        <div className="flex items-center gap-3 text-sm">
                          <Badge className={getCategoryColor(poll.category)}>
                            {formatCategoryName(poll.category)}
                          </Badge>
                          <div className="flex items-center gap-1 text-muted-foreground">
                            <Users className="h-3 w-3" />
                            <span>{poll.totalVotes} votes</span>
                          </div>
                        </div>
                      </div>
                      <Button
                        size="sm"
                        onClick={() => onViewPoll(poll.id)}
                      >
                        View Results
                      </Button>
                    </div>
                  ))
                )}
              </TabsContent>

              <TabsContent value="bookmarked" className="space-y-3">
                {bookmarkedPolls.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <Bookmark className="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>No saved polls</p>
                  </div>
                ) : (
                  bookmarkedPolls.map(poll => (
                    <div 
                      key={poll.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex-1">
                        <h4 className="mb-2">{poll.title}</h4>
                        <div className="flex items-center gap-3 text-sm">
                          <Badge className={getCategoryColor(poll.category)}>
                            {formatCategoryName(poll.category)}
                          </Badge>
                          <div className="flex items-center gap-1 text-muted-foreground">
                            <Users className="h-3 w-3" />
                            <span>{poll.totalVotes} votes</span>
                          </div>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => onBookmark('poll', poll.id)}
                        >
                          <Bookmark className="h-4 w-4 fill-current" />
                        </Button>
                        <Button
                          size="sm"
                          onClick={() => onViewPoll(poll.id)}
                        >
                          {poll.hasVoted ? 'View Results' : 'Vote Now'}
                        </Button>
                      </div>
                    </div>
                  ))
                )}
              </TabsContent>
            </Tabs>
          )}

          {activeTab === 'petitions' && (
            <Tabs defaultValue="created" className="space-y-4">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="created">
                  Created ({createdPetitions.length})
                </TabsTrigger>
                <TabsTrigger value="supported">
                  Supported ({supportedPetitions.length})
                </TabsTrigger>
                <TabsTrigger value="bookmarked">
                  <Bookmark className="h-3 w-3 mr-1" />
                  Saved ({bookmarkedPetitions.length})
                </TabsTrigger>
              </TabsList>

              <TabsContent value="created" className="space-y-3">
                {createdPetitions.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <FileText className="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>You haven't created any petitions yet</p>
                  </div>
                ) : (
                  createdPetitions.map(petition => (
                    <div 
                      key={petition.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex-1">
                        <h4 className="mb-2">{petition.title}</h4>
                        <div className="flex items-center gap-3 text-sm">
                          <div className="flex items-center gap-1 text-muted-foreground">
                            <Users className="h-3 w-3" />
                            <span>{petition.supportCount} supporters</span>
                          </div>
                          <div className="flex items-center gap-1 text-muted-foreground">
                            <Calendar className="h-3 w-3" />
                            <span>{petition.createdAt}</span>
                          </div>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => onBookmark('petition', petition.id)}
                        >
                          <Bookmark className="h-4 w-4" />
                        </Button>
                        <Button
                          size="sm"
                          onClick={() => onViewPetition(petition.id)}
                        >
                          View Details
                        </Button>
                      </div>
                    </div>
                  ))
                )}
              </TabsContent>

              <TabsContent value="supported" className="space-y-3">
                {supportedPetitions.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <CheckCircle2 className="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>You haven't supported any petitions yet</p>
                  </div>
                ) : (
                  supportedPetitions.map(petition => (
                    <div 
                      key={petition.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <h4>{petition.title}</h4>
                          {petition.hasSupported && (
                            <Badge variant="secondary" className="bg-green-100 text-green-700">
                              <CheckCircle2 className="h-3 w-3 mr-1" />
                              Supported
                            </Badge>
                          )}
                        </div>
                        <div className="flex items-center gap-3 text-sm text-muted-foreground">
                          <div className="flex items-center gap-1">
                            <Users className="h-3 w-3" />
                            <span>{petition.supportCount} supporters</span>
                          </div>
                        </div>
                      </div>
                      <Button
                        size="sm"
                        onClick={() => onViewPetition(petition.id)}
                      >
                        View Details
                      </Button>
                    </div>
                  ))
                )}
              </TabsContent>

              <TabsContent value="bookmarked" className="space-y-3">
                {bookmarkedPetitions.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground">
                    <Bookmark className="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>No saved petitions</p>
                  </div>
                ) : (
                  bookmarkedPetitions.map(petition => (
                    <div 
                      key={petition.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex-1">
                        <h4 className="mb-2">{petition.title}</h4>
                        <div className="flex items-center gap-3 text-sm text-muted-foreground">
                          <div className="flex items-center gap-1">
                            <Users className="h-3 w-3" />
                            <span>{petition.supportCount} supporters</span>
                          </div>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => onBookmark('petition', petition.id)}
                        >
                          <Bookmark className="h-4 w-4 fill-current" />
                        </Button>
                        <Button
                          size="sm"
                          onClick={() => onViewPetition(petition.id)}
                        >
                          {petition.hasSupported ? 'View Details' : 'Support Now'}
                        </Button>
                      </div>
                    </div>
                  ))
                )}
              </TabsContent>
            </Tabs>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
