import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Badge } from "../ui/badge";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, LineChart, Line, PieChart, Pie, Cell } from "recharts";
import { Brain, TrendingUp, Users, Clock } from "lucide-react";

interface EmotionData {
  emotion: string;
  percentage: number;
  color: string;
  trend: 'up' | 'down' | 'stable';
}

interface SentimentOverTime {
  time: string;
  positive: number;
  negative: number;
  neutral: number;
}

interface CommentAnalysis {
  id: string;
  content: string;
  author: string;
  emotions: {
    positive: number;
    negative: number;
    neutral: number;
    excited: number;
    concerned: number;
    supportive: number;
  };
  sentiment: 'positive' | 'negative' | 'neutral';
  keyPhrases: string[];
}

interface EmotionAnalysisProps {
  topicId: string;
  comments: any[];
}

export function EmotionAnalysis({ topicId, comments }: EmotionAnalysisProps) {
  // Mock AI analysis data - in real app, this would come from AI service
  const emotionData: EmotionData[] = [
    { emotion: "Supportive", percentage: 45, color: "#22c55e", trend: 'up' },
    { emotion: "Excited", percentage: 25, color: "#3b82f6", trend: 'stable' },
    { emotion: "Concerned", percentage: 20, color: "#f59e0b", trend: 'down' },
    { emotion: "Skeptical", percentage: 10, color: "#ef4444", trend: 'stable' }
  ];

  const sentimentOverTime: SentimentOverTime[] = [
    { time: "Week 1", positive: 60, negative: 25, neutral: 15 },
    { time: "Week 2", positive: 65, negative: 20, neutral: 15 },
    { time: "Week 3", positive: 70, negative: 18, neutral: 12 },
    { time: "Current", positive: 72, negative: 16, neutral: 12 }
  ];

  const overallSentiment = {
    positive: 72,
    negative: 16,
    neutral: 12
  };

  const keyInsights = [
    "Community shows strong support for the proposal with 72% positive sentiment",
    "Main concerns center around implementation costs and timeline",
    "Excitement levels have increased 15% over the past week",
    "Most engaged discussions happen around feasibility questions"
  ];

  const topKeyPhrases = [
    { phrase: "great idea", count: 12, sentiment: "positive" },
    { phrase: "budget concerns", count: 8, sentiment: "negative" },
    { phrase: "community benefit", count: 10, sentiment: "positive" },
    { phrase: "implementation timeline", count: 6, sentiment: "neutral" },
    { phrase: "strongly support", count: 9, sentiment: "positive" }
  ];

  const getPhraseColor = (sentiment: string) => {
    switch (sentiment) {
      case 'positive': return 'bg-green-100 text-green-800';
      case 'negative': return 'bg-red-100 text-red-800';
      case 'neutral': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getTrendIcon = (trend: string) => {
    switch (trend) {
      case 'up': return '↗️';
      case 'down': return '↘️';
      case 'stable': return '→';
      default: return '→';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-2">
        <Brain className="h-5 w-5 text-purple-600" />
        <h3>AI Emotion Analysis</h3>
        <Badge variant="outline" className="bg-purple-50 text-purple-700">
          Powered by AI
        </Badge>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base flex items-center gap-2">
              <Users className="h-4 w-4" />
              Overall Sentiment
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm">Positive</span>
                <span className="text-sm font-medium text-green-600">{overallSentiment.positive}%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div 
                  className="bg-green-500 h-2 rounded-full" 
                  style={{ width: `${overallSentiment.positive}%` }}
                />
              </div>
              <div className="flex justify-between text-xs text-muted-foreground">
                <span>Negative: {overallSentiment.negative}%</span>
                <span>Neutral: {overallSentiment.neutral}%</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base flex items-center gap-2">
              <TrendingUp className="h-4 w-4" />
              Engagement Level
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">High</div>
              <div className="text-sm text-muted-foreground">
                {comments.length} active participants
              </div>
              <div className="text-xs text-green-600 mt-1">
                ↗️ 23% increase this week
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base flex items-center gap-2">
              <Clock className="h-4 w-4" />
              Response Speed
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-center">
              <div className="text-2xl font-bold text-purple-600">2.3hrs</div>
              <div className="text-sm text-muted-foreground">
                Average response time
              </div>
              <div className="text-xs text-green-600 mt-1">
                ↗️ 15% faster than average
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Emotion Breakdown</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={200}>
              <BarChart data={emotionData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="emotion" />
                <YAxis />
                <Tooltip formatter={(value) => [`${value}%`, 'Percentage']} />
                <Bar dataKey="percentage" fill="#8884d8">
                  {emotionData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
            <div className="mt-4 space-y-2">
              {emotionData.map((emotion) => (
                <div key={emotion.emotion} className="flex items-center justify-between text-sm">
                  <div className="flex items-center gap-2">
                    <div 
                      className="w-3 h-3 rounded" 
                      style={{ backgroundColor: emotion.color }}
                    />
                    <span>{emotion.emotion}</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <span>{emotion.percentage}%</span>
                    <span>{getTrendIcon(emotion.trend)}</span>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">Sentiment Trend</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={200}>
              <LineChart data={sentimentOverTime}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="time" />
                <YAxis />
                <Tooltip formatter={(value) => [`${value}%`, '']} />
                <Line type="monotone" dataKey="positive" stroke="#22c55e" strokeWidth={2} />
                <Line type="monotone" dataKey="negative" stroke="#ef4444" strokeWidth={2} />
                <Line type="monotone" dataKey="neutral" stroke="#6b7280" strokeWidth={2} />
              </LineChart>
            </ResponsiveContainer>
            <div className="flex justify-center gap-4 mt-4 text-xs">
              <div className="flex items-center gap-1">
                <div className="w-2 h-2 bg-green-500 rounded" />
                <span>Positive</span>
              </div>
              <div className="flex items-center gap-1">
                <div className="w-2 h-2 bg-red-500 rounded" />
                <span>Negative</span>
              </div>
              <div className="flex items-center gap-1">
                <div className="w-2 h-2 bg-gray-500 rounded" />
                <span>Neutral</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Key Insights</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {keyInsights.map((insight, index) => (
              <div key={index} className="flex items-start gap-2 text-sm">
                <div className="w-1.5 h-1.5 bg-blue-500 rounded-full mt-2 flex-shrink-0" />
                <span>{insight}</span>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Trending Phrases</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-2">
            {topKeyPhrases.map((phrase, index) => (
              <Badge 
                key={index} 
                variant="outline" 
                className={`${getPhraseColor(phrase.sentiment)} flex items-center gap-1`}
              >
                <span>{phrase.phrase}</span>
                <span className="text-xs">({phrase.count})</span>
              </Badge>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}