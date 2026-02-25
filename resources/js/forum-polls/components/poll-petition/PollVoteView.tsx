import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { Progress } from "../ui/progress";
import { Separator } from "../ui/separator";
import { ArrowLeft, Users, Calendar, Clock, ThumbsUp, ThumbsDown, BarChart3 } from "lucide-react";
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid } from "recharts";

interface PollOption {
  id: string;
  text: string;
  votes: number;
}

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
  usefulnessScore?: number;
  targetCriteria?: {
    faculty?: string;
    yearOfStudy?: string;
    course?: string;
  };
}

interface PollVoteViewProps {
  poll: Poll;
  onBack: () => void;
  onVote: (pollId: string, optionId: string) => void;
  onRateUsefulness: (pollId: string, isUseful: boolean) => void;
}

const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

export function PollVoteView({ poll, onBack, onVote, onRateUsefulness }: PollVoteViewProps) {
  const [selectedOption, setSelectedOption] = useState<string | null>(null);
  const [hasRated, setHasRated] = useState(false);

  const handleVote = () => {
    if (selectedOption && !poll.hasVoted && !poll.isExpired) {
      onVote(poll.id, selectedOption);
    }
  };

  const handleRating = (isUseful: boolean) => {
    if (!hasRated && poll.hasVoted) {
      onRateUsefulness(poll.id, isUseful);
      setHasRated(true);
    }
  };

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

  const getPercentage = (votes: number) => {
    return poll.totalVotes > 0 ? ((votes / poll.totalVotes) * 100).toFixed(1) : 0;
  };

  const pieData = poll.options.map((option, index) => ({
    name: option.text,
    value: option.votes,
    percentage: getPercentage(option.votes)
  }));

  const barData = poll.options.map(option => ({
    name: option.text.length > 20 ? option.text.substring(0, 20) + '...' : option.text,
    votes: option.votes,
    percentage: parseFloat(getPercentage(option.votes))
  }));

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <Button onClick={onBack} variant="ghost" className="mb-3 -ml-2">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Polls
          </Button>
          <h1>{poll.title}</h1>
          <div className="flex flex-wrap gap-2 mt-2">
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
                You Voted
              </Badge>
            )}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Poll Description</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-muted-foreground">{poll.description}</p>
              
              <Separator className="my-4" />
              
              <div className="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div className="flex items-center gap-2">
                  <Users className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-muted-foreground">Author</p>
                    <p>{poll.author}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Calendar className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-muted-foreground">Created</p>
                    <p>{poll.createdAt}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Clock className={`h-4 w-4 ${poll.isExpired ? 'text-gray-500' : 'text-orange-500'}`} />
                  <div>
                    <p className="text-muted-foreground">Status</p>
                    <p className={poll.isExpired ? 'text-gray-600' : 'text-orange-600'}>
                      {getDaysRemaining()}
                    </p>
                  </div>
                </div>
              </div>

              {poll.targetCriteria && (
                <>
                  <Separator className="my-4" />
                  <div>
                    <p className="text-sm text-muted-foreground mb-2">Target Audience</p>
                    <div className="flex flex-wrap gap-2">
                      {poll.targetCriteria.faculty && (
                        <Badge variant="outline">
                          {poll.targetCriteria.faculty}
                        </Badge>
                      )}
                      {poll.targetCriteria.yearOfStudy && (
                        <Badge variant="outline">
                          {poll.targetCriteria.yearOfStudy}
                        </Badge>
                      )}
                      {poll.targetCriteria.course && (
                        <Badge variant="outline">
                          {poll.targetCriteria.course}
                        </Badge>
                      )}
                    </div>
                  </div>
                </>
              )}
            </CardContent>
          </Card>

          {!poll.hasVoted && !poll.isExpired ? (
            <Card>
              <CardHeader>
                <CardTitle>Cast Your Vote</CardTitle>
                <p className="text-sm text-muted-foreground">
                  Select one option below and submit your vote
                </p>
              </CardHeader>
              <CardContent className="space-y-3">
                {poll.options.map((option) => (
                  <div
                    key={option.id}
                    onClick={() => setSelectedOption(option.id)}
                    className={`p-4 rounded-lg border-2 cursor-pointer transition-all ${
                      selectedOption === option.id
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                        selectedOption === option.id
                          ? 'border-blue-500 bg-blue-500'
                          : 'border-gray-300'
                      }`}>
                        {selectedOption === option.id && (
                          <div className="w-2 h-2 bg-white rounded-full" />
                        )}
                      </div>
                      <p>{option.text}</p>
                    </div>
                  </div>
                ))}
                
                <Button 
                  onClick={handleVote} 
                  disabled={!selectedOption}
                  className="w-full mt-4"
                >
                  Submit Vote
                </Button>
              </CardContent>
            </Card>
          ) : (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <BarChart3 className="h-5 w-5" />
                  Poll Results
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                  {poll.totalVotes} {poll.totalVotes === 1 ? 'vote' : 'votes'} recorded
                </p>
              </CardHeader>
              <CardContent className="space-y-4">
                {poll.options.map((option, index) => (
                  <div key={option.id} className="space-y-2">
                    <div className="flex items-center justify-between">
                      <p className="text-sm">{option.text}</p>
                      <div className="flex items-center gap-2">
                        <Badge variant="secondary">
                          {option.votes} {option.votes === 1 ? 'vote' : 'votes'}
                        </Badge>
                        <span className="text-sm font-medium">{getPercentage(option.votes)}%</span>
                      </div>
                    </div>
                    <Progress 
                      value={parseFloat(getPercentage(option.votes))} 
                      className="h-2"
                      style={{ 
                        backgroundColor: '#e5e7eb'
                      }}
                    />
                  </div>
                ))}

                {poll.hasVoted && !hasRated && (
                  <>
                    <Separator className="my-6" />
                    <div className="space-y-3">
                      <p className="text-sm">Was this poll useful to you?</p>
                      <div className="flex gap-3">
                        <Button
                          onClick={() => handleRating(true)}
                          variant="outline"
                          className="flex-1"
                        >
                          <ThumbsUp className="h-4 w-4 mr-2" />
                          Yes
                        </Button>
                        <Button
                          onClick={() => handleRating(false)}
                          variant="outline"
                          className="flex-1"
                        >
                          <ThumbsDown className="h-4 w-4 mr-2" />
                          No
                        </Button>
                      </div>
                    </div>
                  </>
                )}

                {hasRated && (
                  <div className="p-3 bg-green-50 border border-green-200 rounded-lg">
                    <p className="text-sm text-green-800">Thank you for your feedback!</p>
                  </div>
                )}
              </CardContent>
            </Card>
          )}
        </div>

        {(poll.hasVoted || poll.isExpired) && poll.totalVotes > 0 && (
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Vote Distribution</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={250}>
                  <PieChart>
                    <Pie
                      data={pieData}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ percentage }) => `${percentage}%`}
                      outerRadius={80}
                      fill="#8884d8"
                      dataKey="value"
                    >
                      {pieData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                      ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-base">Votes Comparison</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={250}>
                  <BarChart data={barData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis 
                      dataKey="name" 
                      tick={{ fontSize: 11 }}
                      interval={0}
                      angle={-45}
                      textAnchor="end"
                      height={80}
                    />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="votes" fill="#3b82f6" />
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            {poll.usefulnessScore !== undefined && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Usefulness Rating</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-center">
                    <div className="text-3xl font-bold mb-2">{poll.usefulnessScore}%</div>
                    <p className="text-sm text-muted-foreground">
                      of voters found this poll useful
                    </p>
                    {poll.usefulnessScore < 30 && (
                      <Badge variant="secondary" className="mt-3 bg-yellow-100 text-yellow-800">
                        Flagged for Review
                      </Badge>
                    )}
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
