import { Badge } from "../ui/badge";
import { Card, CardContent, CardHeader } from "../ui/card";
import { Button } from "../ui/button";
import { Clock, Users, BarChart3, Calendar, Bookmark } from "lucide-react";

interface Poll {
  id: string;
  title: string;
  description: string;
  options: PollOption[];
  category: string;
  author: string;
  createdAt: string;
  expiryDate: string;
  totalVotes: number;
  hasVoted: boolean;
  isExpired: boolean;
  targetCriteria?: {
    faculty?: string;
    yearOfStudy?: string;
    course?: string;
  };
}

interface PollOption {
  id: string;
  text: string;
  votes: number;
}

interface PollCardProps {
  poll: Poll;
  onViewPoll: (pollId: string) => void;
  isBookmarked?: boolean;
  onToggleBookmark?: (pollId: string) => void;
}

export function PollCard({ poll, onViewPoll, isBookmarked = false, onToggleBookmark }: PollCardProps) {
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

  const getDaysRemaining = () => {
    const today = new Date();
    const expiry = new Date(poll.expiryDate);
    const diffTime = expiry.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return "Expired";
    if (diffDays === 0) return "Expires today";
    if (diffDays === 1) return "1 day left";
    return `${diffDays} days left`;
  };

  return (
    <Card className={`hover:shadow-md transition-shadow ${poll.isExpired ? 'opacity-75' : ''}`}>
      <CardHeader className="space-y-3">
        <div className="flex items-start justify-between gap-2">
          <div className="flex-1">
            <div className="flex items-start justify-between gap-2 mb-2">
              <h3 className="leading-snug">{poll.title}</h3>
              {onToggleBookmark && (
                <button
                  onClick={(e) => { e.stopPropagation(); onToggleBookmark(poll.id); }}
                  className="cursor-pointer hover:scale-110 transition-transform flex-shrink-0 mt-0.5"
                  title={isBookmarked ? 'Remove bookmark' : 'Bookmark this poll'}
                >
                  <Bookmark className={`h-5 w-5 ${isBookmarked ? 'text-yellow-500 fill-yellow-500' : 'text-gray-400 hover:text-yellow-500'}`} />
                </button>
              )}
            </div>
            <div className="flex flex-wrap gap-2 mb-2">
              <Badge className={getCategoryColor(poll.category)}>
                {formatCategoryName(poll.category)}
              </Badge>
              {poll.isExpired && (
                <Badge variant="secondary" className="bg-gray-200 text-gray-700">
                  Expired
                </Badge>
              )}
              {poll.hasVoted && !poll.isExpired && (
                <Badge variant="secondary" className="bg-green-100 text-green-700">
                  Voted
                </Badge>
              )}
              {poll.targetCriteria && (
                <Badge variant="secondary" className="bg-amber-100 text-amber-800 border border-amber-300">
                  Targeted
                </Badge>
              )}
            </div>
          </div>
        </div>
        
        <p className="text-sm text-muted-foreground line-clamp-2">
          {poll.description}
        </p>
      </CardHeader>

      <CardContent className="space-y-4">
        <div className="flex items-center justify-between text-sm">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-1 text-muted-foreground">
              <Users className="h-4 w-4" />
              <span>{poll.totalVotes} votes</span>
            </div>
            <div className="flex items-center gap-1 text-muted-foreground">
              <Calendar className="h-4 w-4" />
              <span>{poll.createdAt}</span>
            </div>
          </div>
          
          <div className={`flex items-center gap-1 text-sm ${
            poll.isExpired ? 'text-gray-500' : 'text-orange-600'
          }`}>
            <Clock className="h-4 w-4" />
            <span>{getDaysRemaining()}</span>
          </div>
        </div>

        {poll.targetCriteria && (
          <div className="flex flex-wrap gap-1">
            {poll.targetCriteria.faculty && (
              <Badge variant="outline" className="text-xs">
                {poll.targetCriteria.faculty}
              </Badge>
            )}
            {poll.targetCriteria.yearOfStudy && (
              <Badge variant="outline" className="text-xs">
                {poll.targetCriteria.yearOfStudy}
              </Badge>
            )}
            {poll.targetCriteria.course && (
              <Badge variant="outline" className="text-xs">
                {poll.targetCriteria.course}
              </Badge>
            )}
          </div>
        )}

        <Button 
          onClick={() => onViewPoll(poll.id)} 
          className="w-full cursor-pointer"
          variant={poll.hasVoted || poll.isExpired ? "outline" : "default"}
        >
          <BarChart3 className="h-4 w-4 mr-2" />
          {poll.hasVoted ? "View Results" : poll.isExpired ? "View Results" : "Vote Now"}
        </Button>
      </CardContent>
    </Card>
  );
}
