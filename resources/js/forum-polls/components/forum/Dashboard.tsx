import { useState } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Badge } from "../ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../ui/tabs";
import { TopicCard } from "./TopicCard";
import { Avatar, AvatarFallback } from "../ui/avatar";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Plus, Search, Filter, MessageCircle, Vote, User, Trophy, Star, Award, Crown, Users } from "lucide-react";

interface Topic {
  id: string;
  title: string;
  description: string;
  author: string;
  createdAt: string;
  status: 'discussion' | 'voting' | 'petition';
  votes: number;
  comments: number;
  participants: number;
  votesNeeded: number;
  category: string;
}

interface UserProfile {
  id: string;
  nickname: string;
  joinedDate: string;
  totalComments: number;
  acceptedTopics: number;
  likesReceived: number;
  topicsCreated: number;
  currentLevel: BadgeLevel;
  activityPoints: number;
}

interface BadgeLevel {
  id: number;
  name: string;
  icon: React.ComponentType<any>;
  color: string;
  minPoints: number;
  maxPoints: number;
}

interface DashboardProps {
  topics: Topic[];
  userProfile: UserProfile;
  onCreateTopic: () => void;
  onViewTopic: (topicId: string) => void;
  onViewProfile: () => void;
  onViewCommunity: () => void;
  onVote: (topicId: string) => void;
}

export function Dashboard({ topics, userProfile, onCreateTopic, onViewTopic, onViewProfile, onViewCommunity, onVote }: DashboardProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("all");
  const [activeTab, setActiveTab] = useState("all");

  const filteredTopics = (status?: string) => {
    return topics.filter(topic => {
      const matchesSearch = topic.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           topic.description.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesCategory = categoryFilter === "all" || topic.category === categoryFilter;
      const matchesStatus = !status || topic.status === status;
      
      return matchesSearch && matchesCategory && matchesStatus;
    });
  };

  const getTopicCounts = () => {
    return {
      total: topics.length,
      discussion: topics.filter(t => t.status === 'discussion').length,
      voting: topics.filter(t => t.status === 'voting').length,
      petition: topics.filter(t => t.status === 'petition').length,
    };
  };

  const counts = getTopicCounts();
  const categories = Array.from(new Set(topics.map(t => t.category)));

  const badgeLevels: BadgeLevel[] = [
    {
      id: 1,
      name: "Newcomer",
      icon: User,
      color: "bg-gray-100 text-gray-800",
      minPoints: 0,
      maxPoints: 10
    },
    {
      id: 2,
      name: "Contributor",
      icon: Star,
      color: "bg-blue-100 text-blue-800",
      minPoints: 11,
      maxPoints: 50
    },
    {
      id: 3,
      name: "Active Member",
      icon: Award,
      color: "bg-green-100 text-green-800",
      minPoints: 51,
      maxPoints: 150
    },
    {
      id: 4,
      name: "Community Leader",
      icon: Trophy,
      color: "bg-purple-100 text-purple-800",
      minPoints: 151,
      maxPoints: 500
    },
    {
      id: 5,
      name: "Expert",
      icon: Crown,
      color: "bg-yellow-100 text-yellow-800",
      minPoints: 501,
      maxPoints: Infinity
    }
  ];

  const calculateLevel = (points: number): BadgeLevel => {
    return badgeLevels.find(level => points >= level.minPoints && points <= level.maxPoints) || badgeLevels[0];
  };

  const currentLevel = calculateLevel(userProfile.activityPoints);
  const IconComponent = currentLevel.icon;

  const renderTopicGrid = (topicList: Topic[]) => (
    topicList.length === 0 ? (
      <div className="text-center py-12">
        <p className="text-muted-foreground">
          {searchTerm || categoryFilter !== "all" 
            ? "No topics match your current filters."
            : "No topics in this category yet."
          }
        </p>
      </div>
    ) : (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {topicList.map(topic => (
          <TopicCard
            key={topic.id}
            topic={topic}
            onViewTopic={onViewTopic}
            onVote={onVote}
          />
        ))}
      </div>
    )
  );

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1>Community Petitions</h1>
          <p className="text-muted-foreground">
            Discuss ideas, build consensus, and create meaningful change together
          </p>
        </div>
        <div className="flex items-center gap-3">
          <Button 
            variant="ghost" 
            onClick={onViewCommunity}
            className="flex items-center gap-2"
          >
            <Users className="h-4 w-4" />
            Designing Community
          </Button>
          <Button 
            variant="outline" 
            onClick={onViewProfile}
            className="flex items-center gap-3"
          >
            <Avatar className="h-8 w-8">
              <AvatarFallback className="text-sm">
                {userProfile.nickname.charAt(0).toUpperCase()}
              </AvatarFallback>
            </Avatar>
            <div className="flex flex-col items-start">
              <span className="text-sm">{userProfile.nickname}</span>
              <div className="flex items-center gap-2">
                <div className="h-4 w-4 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center">
                  <IconComponent className="h-2.5 w-2.5 text-blue-600" />
                </div>
                <Badge className={`${currentLevel.color} text-xs`} variant="secondary">
                  {currentLevel.name}
                </Badge>
              </div>
            </div>
          </Button>
          <Button onClick={onCreateTopic}>
            <Plus className="h-4 w-4 mr-2" />
            Create Topic
          </Button>
        </div>
      </div>

      <div className="flex gap-4 items-center flex-wrap">
        <Badge variant="outline">
          Total: {counts.total}
        </Badge>
        <Badge variant="outline" className="bg-blue-50 text-blue-700">
          Discussion: {counts.discussion}
        </Badge>
        <Badge variant="outline" className="bg-yellow-50 text-yellow-700">
          Voting: {counts.voting}
        </Badge>
        <Badge variant="outline" className="bg-green-50 text-green-700">
          Petitions: {counts.petition}
        </Badge>
      </div>

      <div className="flex gap-4 items-center">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
          <Input
            placeholder="Search topics..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>

        <Select value={categoryFilter} onValueChange={setCategoryFilter}>
          <SelectTrigger className="w-48">
            <Filter className="h-4 w-4 mr-2" />
            <SelectValue placeholder="Filter by category" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Categories</SelectItem>
            {categories.map(category => (
              <SelectItem key={category} value={category}>
                {category.charAt(0).toUpperCase() + category.slice(1)}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="all">All Topics</TabsTrigger>
          <TabsTrigger value="discussion" className="flex items-center gap-2">
            <MessageCircle className="h-4 w-4" />
            Discussion ({counts.discussion})
          </TabsTrigger>
          <TabsTrigger value="voting" className="flex items-center gap-2">
            <Vote className="h-4 w-4" />
            Voting ({counts.voting})
          </TabsTrigger>
          <TabsTrigger value="petition">
            Petitions ({counts.petition})
          </TabsTrigger>
        </TabsList>

        <TabsContent value="all" className="space-y-4">
          <div className="space-y-8">
            {counts.discussion > 0 && (
              <div className="space-y-4">
                <div className="flex items-center gap-2">
                  <MessageCircle className="h-5 w-5 text-blue-600" />
                  <h2 className="text-lg">Open for Discussion</h2>
                  <Badge variant="outline" className="bg-blue-50 text-blue-700">
                    {counts.discussion} topics
                  </Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                  These topics are in active discussion. Join the conversation to help shape ideas before they move to voting.
                </p>
                {renderTopicGrid(filteredTopics('discussion'))}
              </div>
            )}

            {counts.voting > 0 && (
              <div className="space-y-4">
                <div className="flex items-center gap-2">
                  <Vote className="h-5 w-5 text-yellow-600" />
                  <h2 className="text-lg">Open for Voting</h2>
                  <Badge variant="outline" className="bg-yellow-50 text-yellow-700">
                    {counts.voting} topics
                  </Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                  These topics have moved to the voting phase. Cast your vote to help them become official petitions.
                </p>
                {renderTopicGrid(filteredTopics('voting'))}
              </div>
            )}

            {counts.petition > 0 && (
              <div className="space-y-4">
                <div className="flex items-center gap-2">
                  <h2 className="text-lg">Official Petitions</h2>
                  <Badge variant="outline" className="bg-green-50 text-green-700">
                    {counts.petition} petitions
                  </Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                  These topics have reached their vote threshold and are now official petitions.
                </p>
                {renderTopicGrid(filteredTopics('petition'))}
              </div>
            )}

            {counts.total === 0 && (
              <div className="text-center py-12">
                <p className="text-muted-foreground">
                  No topics yet. Be the first to create one!
                </p>
              </div>
            )}
          </div>
        </TabsContent>

        <TabsContent value="discussion">
          <div className="space-y-4">
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h3 className="text-blue-900 mb-2">Discussion Phase</h3>
              <p className="text-blue-800 text-sm">
                Topics in this phase are open for community input and refinement. 
                Share your thoughts, suggest improvements, and help build consensus before moving to the voting phase.
              </p>
            </div>
            {renderTopicGrid(filteredTopics('discussion'))}
          </div>
        </TabsContent>

        <TabsContent value="voting">
          <div className="space-y-4">
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <h3 className="text-yellow-900 mb-2">Voting Phase</h3>
              <p className="text-yellow-800 text-sm">
                These topics have completed the discussion phase and are now open for community voting. 
                Vote for the topics you support to help them become official petitions.
              </p>
            </div>
            {renderTopicGrid(filteredTopics('voting'))}
          </div>
        </TabsContent>

        <TabsContent value="petition">
          <div className="space-y-4">
            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
              <h3 className="text-green-900 mb-2">Official Petitions</h3>
              <p className="text-green-800 text-sm">
                These topics have successfully reached their vote threshold and are now official petitions 
                ready for submission to relevant authorities.
              </p>
            </div>
            {renderTopicGrid(filteredTopics('petition'))}
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}