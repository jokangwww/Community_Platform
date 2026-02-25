import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Badge } from "../ui/badge";
import { Progress } from "../ui/progress";
import { Search, Filter, Calendar, Users, TrendingUp, ArrowLeft, BarChart3 } from "lucide-react";

interface PollOption {
  id: string;
  text: string;
  votes: number;
}

interface ArchivedPoll {
  id: string;
  title: string;
  description: string;
  options: PollOption[];
  category: string;
  author: string;
  createdAt: string;
  expiryDate: string;
  totalVotes: number;
  usefulnessScore: number;
}

interface PollArchiveProps {
  archivedPolls: ArchivedPoll[];
  onBack: () => void;
  onViewPoll: (pollId: string) => void;
}

export function PollArchive({ archivedPolls, onBack, onViewPoll }: PollArchiveProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("all");
  const [sortBy, setSortBy] = useState("date");

  const categories = Array.from(new Set(archivedPolls.map(p => p.category)));

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

  const getPercentage = (votes: number, total: number) => {
    return total > 0 ? ((votes / total) * 100).toFixed(1) : 0;
  };

  const filteredPolls = archivedPolls.filter(poll => {
    const matchesSearch = poll.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         poll.description.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = categoryFilter === "all" || poll.category === categoryFilter;
    return matchesSearch && matchesCategory;
  });

  const sortedPolls = [...filteredPolls].sort((a, b) => {
    switch (sortBy) {
      case "popularity":
        return b.totalVotes - a.totalVotes;
      case "usefulness":
        return b.usefulnessScore - a.usefulnessScore;
      case "date":
      default:
        return new Date(b.expiryDate).getTime() - new Date(a.expiryDate).getTime();
    }
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <Button onClick={onBack} variant="ghost" className="mb-3 -ml-2">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Polls
          </Button>
          <h1>Poll Archive</h1>
          <p className="text-muted-foreground">
            Browse completed polls and analyze their results
          </p>
        </div>
      </div>

      <div className="flex flex-wrap gap-4 items-center">
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
          <Input
            placeholder="Search archived polls..."
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
                {formatCategoryName(category)}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Select value={sortBy} onValueChange={setSortBy}>
          <SelectTrigger className="w-48">
            <TrendingUp className="h-4 w-4 mr-2" />
            <SelectValue placeholder="Sort by" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="date">Most Recent</SelectItem>
            <SelectItem value="popularity">Most Popular</SelectItem>
            <SelectItem value="usefulness">Most Useful</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="grid grid-cols-1 gap-4">
        {sortedPolls.length === 0 ? (
          <Card>
            <CardContent className="py-12 text-center">
              <p className="text-muted-foreground">
                {searchTerm || categoryFilter !== "all"
                  ? "No archived polls match your current filters."
                  : "No archived polls yet."}
              </p>
            </CardContent>
          </Card>
        ) : (
          sortedPolls.map((poll) => {
            const topOption = poll.options.reduce((prev, current) => 
              current.votes > prev.votes ? current : prev
            );
            
            return (
              <Card key={poll.id} className="hover:shadow-md transition-shadow">
                <CardHeader>
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <h3 className="text-lg">{poll.title}</h3>
                      </div>
                      <div className="flex flex-wrap gap-2 mb-2">
                        <Badge className={getCategoryColor(poll.category)}>
                          {formatCategoryName(poll.category)}
                        </Badge>
                        {poll.usefulnessScore < 30 && (
                          <Badge variant="secondary" className="bg-yellow-100 text-yellow-800">
                            Low Usefulness
                          </Badge>
                        )}
                      </div>
                      <p className="text-sm text-muted-foreground">{poll.description}</p>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="flex items-center gap-1 text-sm text-muted-foreground mb-1">
                        <Users className="h-4 w-4" />
                        <span>{poll.totalVotes} votes</span>
                      </div>
                      <div className="flex items-center gap-1 text-sm text-muted-foreground">
                        <Calendar className="h-4 w-4" />
                        <span>{poll.expiryDate}</span>
                      </div>
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-3">
                    {poll.options.map((option) => (
                      <div key={option.id} className="space-y-1">
                        <div className="flex items-center justify-between text-sm">
                          <span>{option.text}</span>
                          <div className="flex items-center gap-2">
                            <Badge variant="secondary" className="text-xs">
                              {option.votes}
                            </Badge>
                            <span className="font-medium">
                              {getPercentage(option.votes, poll.totalVotes)}%
                            </span>
                          </div>
                        </div>
                        <Progress 
                          value={parseFloat(getPercentage(option.votes, poll.totalVotes))} 
                          className="h-1.5"
                        />
                      </div>
                    ))}
                  </div>

                  <div className="flex items-center justify-between pt-3 border-t">
                    <div className="flex items-center gap-4 text-sm">
                      <div>
                        <span className="text-muted-foreground">Winning Option: </span>
                        <span className="font-medium">{topOption.text}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground">Usefulness: </span>
                        <span className={`font-medium ${
                          poll.usefulnessScore >= 70 ? 'text-green-600' : 
                          poll.usefulnessScore >= 30 ? 'text-orange-600' : 
                          'text-red-600'
                        }`}>
                          {poll.usefulnessScore}%
                        </span>
                      </div>
                    </div>
                    <Button 
                      variant="outline" 
                      size="sm"
                      onClick={() => onViewPoll(poll.id)}
                    >
                      <BarChart3 className="h-4 w-4 mr-2" />
                      View Details
                    </Button>
                  </div>
                </CardContent>
              </Card>
            );
          })
        )}
      </div>
    </div>
  );
}
