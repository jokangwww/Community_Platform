import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Textarea } from "../ui/textarea";
import { Badge } from "../ui/badge";
import { Avatar, AvatarImage, AvatarFallback } from "../ui/avatar";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../ui/tabs";
import { InternationalPerspectives } from "./InternationalPerspectives";
import { BrainstormingView } from "./BrainstormingView";
import { EmotionAnalysis } from "./EmotionAnalysis";
import { ArrowLeft, MessageCircle, ThumbsUp, Send, Reply, Globe, Users, Plus, Lightbulb, Brain, BarChart3, Check, X } from "lucide-react";

interface Comment {
  id: string;
  author: string;
  content: string;
  timestamp: string;
  replies?: Comment[];
}

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
  fullDescription: string;
  agreeCount?: number;
  disagreeCount?: number;
  totalVotes?: number;
  hasUserAgreed?: boolean;
  hasUserDisagreed?: boolean;
}

interface DiscussionViewProps {
  topic: Topic;
  comments: Comment[];
  onBack: () => void;
  onAddComment: (content: string) => void;
  onReply: (commentId: string, content: string) => void;
  onVote: () => void;
  onAgreeDisagree?: (isAgree: boolean) => void;
}

export function DiscussionView({ topic, comments, onBack, onAddComment, onReply, onVote, onAgreeDisagree }: DiscussionViewProps) {
  const [newComment, setNewComment] = useState("");
  const [replyingTo, setReplyingTo] = useState<string | null>(null);
  const [replyContent, setReplyContent] = useState("");
  const [activeTab, setActiveTab] = useState("brainstorming");

  const handleSubmitComment = () => {
    if (newComment.trim()) {
      onAddComment(newComment);
      setNewComment("");
    }
  };

  const handleSubmitReply = (commentId: string) => {
    if (replyContent.trim()) {
      onReply(commentId, replyContent);
      setReplyContent("");
      setReplyingTo(null);
    }
  };

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
  
  // For agree/disagree system
  const totalVotes = topic.totalVotes || 0;
  const agreeCount = topic.agreeCount || 0;
  const disagreeCount = topic.disagreeCount || 0;
  const agreementPercentage = totalVotes > 0 ? Math.round((agreeCount / totalVotes) * 100) : 0;
  const disagreementPercentage = totalVotes > 0 ? Math.round((disagreeCount / totalVotes) * 100) : 0;

  const handleAgreeDisagree = (isAgree: boolean) => {
    if (onAgreeDisagree) {
      onAgreeDisagree(isAgree);
    }
  };

  const renderComment = (comment: Comment, isReply = false) => (
    <div key={comment.id} className={`space-y-3 ${isReply ? 'ml-8 border-l-2 border-gray-200 pl-4' : ''}`}>
      <div className="flex items-start gap-3">
        <Avatar className="h-8 w-8">
          <AvatarFallback>{comment.author.charAt(0)}</AvatarFallback>
        </Avatar>
        <div className="flex-1 space-y-2">
          <div className="flex items-center gap-2">
            <span className="font-medium">{comment.author}</span>
            <span className="text-sm text-muted-foreground">{comment.timestamp}</span>
          </div>
          <p className="text-sm">{comment.content}</p>
          {!isReply && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setReplyingTo(comment.id)}
              className="h-8 px-2"
            >
              <Reply className="h-3 w-3 mr-1" />
              Reply
            </Button>
          )}
        </div>
      </div>
      
      {replyingTo === comment.id && (
        <div className="ml-11 space-y-2">
          <Textarea
            placeholder="Write your reply..."
            value={replyContent}
            onChange={(e) => setReplyContent(e.target.value)}
            className="min-h-20"
          />
          <div className="flex gap-2">
            <Button 
              size="sm" 
              onClick={() => handleSubmitReply(comment.id)}
              disabled={!replyContent.trim()}
            >
              Reply
            </Button>
            <Button 
              variant="outline" 
              size="sm" 
              onClick={() => {
                setReplyingTo(null);
                setReplyContent("");
              }}
            >
              Cancel
            </Button>
          </div>
        </div>
      )}
      
      {comment.replies && comment.replies.map(reply => renderComment(reply, true))}
    </div>
  );

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" onClick={onBack}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back to Topics
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex justify-between items-start">
            <CardTitle>{topic.title}</CardTitle>
            <Badge className={getStatusColor(topic.status)}>
              {getStatusText(topic.status)}
            </Badge>
          </div>
          <p className="text-muted-foreground">by {topic.author} • {topic.createdAt}</p>
        </CardHeader>
        <CardContent className="space-y-4">
          <p>{topic.fullDescription}</p>
          
          <div className="flex items-center gap-4 text-sm text-muted-foreground">
            <div className="flex items-center gap-1">
              <MessageCircle className="h-4 w-4" />
              {topic.comments} comments
            </div>
            <div className="flex items-center gap-1">
              <ThumbsUp className="h-4 w-4" />
              {topic.votes}/{topic.votesNeeded} votes
            </div>
            <div className="flex items-center gap-1">
              <Users className="h-4 w-4" />
              {topic.participants} participants
            </div>
          </div>

          {topic.status === 'voting' && (
            <div className="space-y-3">
              <div className="w-full bg-gray-200 rounded-full h-3">
                <div 
                  className="bg-primary h-3 rounded-full transition-all duration-300"
                  style={{ width: `${Math.min(progressPercentage, 100)}%` }}
                />
              </div>
              <div className="flex justify-between items-center">
                <p className="text-sm text-muted-foreground">
                  {topic.votesNeeded - topic.votes} more votes needed
                </p>
                <Button onClick={onVote}>
                  <ThumbsUp className="h-4 w-4 mr-2" />
                  Vote for this topic
                </Button>
              </div>
            </div>
          )}

          {topic.status === 'discussion' && (
            <div className="space-y-3">
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p className="text-sm text-blue-800">
                  💡 This topic is in the discussion phase. Use the Brainstorming tab to collaborate on improving the proposal!
                </p>
              </div>
              
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="text-sm">What do you think of this proposal?</span>
                  <div className="flex items-center gap-1">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleAgreeDisagree(true)}
                      className={`h-6 px-2 text-xs ${topic.hasUserAgreed ? 'bg-pink-100 text-pink-700 border-pink-300' : 'hover:bg-pink-50'}`}
                    >
                      <Check className="h-2.5 w-2.5 mr-1" />
                      Agree ({agreeCount})
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleAgreeDisagree(false)}
                      className={`h-6 px-2 text-xs ${topic.hasUserDisagreed ? 'bg-sky-100 text-sky-700 border-sky-300' : 'hover:bg-sky-50'}`}
                    >
                      <X className="h-2.5 w-2.5 mr-1" />
                      Disagree ({disagreeCount})
                    </Button>
                  </div>
                </div>
              </div>

              {totalVotes > 0 && (
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
            </div>
          )}
        </CardContent>
      </Card>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-5">
          <TabsTrigger value="brainstorming" className="flex items-center gap-2">
            <Lightbulb className="h-4 w-4" />
            Brainstorming
          </TabsTrigger>
          <TabsTrigger value="emotions" className="flex items-center gap-2">
            <Brain className="h-4 w-4" />
            AI Insights
          </TabsTrigger>
          <TabsTrigger value="discussion" className="flex items-center gap-2">
            <MessageCircle className="h-4 w-4" />
            Discussion ({comments.length})
          </TabsTrigger>
          <TabsTrigger value="international" className="flex items-center gap-2">
            <Globe className="h-4 w-4" />
            Global Views
          </TabsTrigger>
          <TabsTrigger value="resources">
            Resources
          </TabsTrigger>
        </TabsList>

        <TabsContent value="brainstorming">
          <BrainstormingView 
            topicId={topic.id} 
            topicTitle={topic.title}
            originalProposal={topic.fullDescription}
          />
        </TabsContent>

        <TabsContent value="emotions">
          <EmotionAnalysis 
            topicId={topic.id}
            comments={comments}
          />
        </TabsContent>

        <TabsContent value="discussion" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>General Discussion</CardTitle>
              <p className="text-sm text-muted-foreground">
                Share your thoughts, ask questions, and have conversations about this topic
              </p>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-4">
                <Textarea
                  placeholder="Join the discussion..."
                  value={newComment}
                  onChange={(e) => setNewComment(e.target.value)}
                  className="min-h-24"
                />
                <Button 
                  onClick={handleSubmitComment}
                  disabled={!newComment.trim()}
                >
                  <Send className="h-4 w-4 mr-2" />
                  Post Comment
                </Button>
              </div>

              <div className="space-y-6">
                {comments.length > 0 ? (
                  comments.map(comment => renderComment(comment))
                ) : (
                  <div className="text-center py-8 text-muted-foreground">
                    <MessageCircle className="h-12 w-12 mx-auto mb-4 opacity-50" />
                    <p>No comments yet. Start the discussion!</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="international">
          <InternationalPerspectives 
            topicId={topic.id} 
            topicTitle={topic.title} 
          />
        </TabsContent>

        <TabsContent value="resources" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Research & Resources</CardTitle>
              <p className="text-sm text-muted-foreground">
                Supporting materials, studies, and references for this topic
              </p>
            </CardHeader>
            <CardContent>
              <div className="text-center py-8 text-muted-foreground">
                <div className="space-y-4">
                  <p>No resources have been shared yet for this topic.</p>
                  <Button variant="outline">
                    <Plus className="h-4 w-4 mr-2" />
                    Add Resource
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}