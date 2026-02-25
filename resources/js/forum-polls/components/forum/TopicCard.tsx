import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Badge } from "../ui/badge";
import { Button } from "../ui/button";
import { MessageCircle, ThumbsUp, Users, Calendar } from "lucide-react";

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
}

interface TopicCardProps {
  topic: Topic;
  onViewTopic: (topicId: string) => void;
  onVote: (topicId: string) => void;
}

export function TopicCard({ topic, onViewTopic, onVote }: TopicCardProps) {
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'discussion': return 'bg-blue-100 text-blue-800';
      case 'voting': return 'bg-yellow-100 text-yellow-800';
      case 'petition': return 'bg-green-100 text-green-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'discussion': return 'In Discussion';
      case 'voting': return 'Open for Voting';
      case 'petition': return 'Ready to Petition';
      default: return status;
    }
  };

  const progressPercentage = (topic.votes / topic.votesNeeded) * 100;

  return (
    <Card className="hover:shadow-md transition-shadow">
      <CardHeader>
        <div className="flex justify-between items-start">
          <CardTitle className="line-clamp-2">{topic.title}</CardTitle>
          <Badge className={getStatusColor(topic.status)}>
            {getStatusText(topic.status)}
          </Badge>
        </div>
        <p className="text-muted-foreground line-clamp-3">{topic.description}</p>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <div className="flex items-center justify-between text-sm text-muted-foreground">
            <div className="flex items-center gap-1">
              <Calendar className="h-4 w-4" />
              {topic.createdAt}
            </div>
            <span>by {topic.author}</span>
          </div>

          <div className="flex items-center gap-4 text-sm">
            <div className="flex items-center gap-1">
              <MessageCircle className="h-4 w-4" />
              {topic.comments}
            </div>
            <div className="flex items-center gap-1">
              <Users className="h-4 w-4" />
              {topic.participants}
            </div>
            <div className="flex items-center gap-1">
              <ThumbsUp className="h-4 w-4" />
              {topic.votes}/{topic.votesNeeded}
            </div>
          </div>

          {topic.status === 'voting' && (
            <div className="space-y-2">
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div 
                  className="bg-primary h-2 rounded-full transition-all duration-300"
                  style={{ width: `${Math.min(progressPercentage, 100)}%` }}
                />
              </div>
              <p className="text-xs text-muted-foreground">
                {topic.votesNeeded - topic.votes} more votes needed
              </p>
            </div>
          )}

          <div className="flex gap-2">
            <Button 
              variant="outline" 
              className="flex-1"
              onClick={() => onViewTopic(topic.id)}
            >
              View Discussion
            </Button>
            {topic.status === 'voting' && (
              <Button 
                onClick={() => onVote(topic.id)}
                className="flex-1"
              >
                Vote
              </Button>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}