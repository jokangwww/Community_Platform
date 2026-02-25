import { Button } from "../ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Badge } from "../ui/badge";
import { Separator } from "../ui/separator";
import { 
  Trophy, 
  MessageSquare, 
  FileText, 
  ThumbsUp, 
  Users, 
  Star,
  Award,
  Crown,
  User,
  ArrowLeft,
  Lightbulb,
  Target,
  Heart
} from "lucide-react";

interface DesigningCommunityProps {
  onBack: () => void;
}

export function DesigningCommunity({ onBack }: DesigningCommunityProps) {
  const badgeLevels = [
    {
      id: 1,
      name: "Newcomer",
      icon: User,
      color: "bg-gray-100 text-gray-800",
      minPoints: 0,
      maxPoints: 10,
      description: "Welcome to the community! Start by exploring topics and joining discussions."
    },
    {
      id: 2,
      name: "Contributor",
      icon: Star,
      color: "bg-blue-100 text-blue-800",
      minPoints: 11,
      maxPoints: 50,
      description: "You're actively participating! Your voice is being heard in the community."
    },
    {
      id: 3,
      name: "Active Member",
      icon: Award,
      color: "bg-green-100 text-green-800",
      minPoints: 51,
      maxPoints: 150,
      description: "A valued community member who regularly contributes meaningful content."
    },
    {
      id: 4,
      name: "Community Leader",
      icon: Trophy,
      color: "bg-purple-100 text-purple-800",
      minPoints: 151,
      maxPoints: 500,
      description: "A respected leader who helps guide community discussions and initiatives."
    },
    {
      id: 5,
      name: "Expert",
      icon: Crown,
      color: "bg-yellow-100 text-yellow-800",
      minPoints: 501,
      maxPoints: Infinity,
      description: "The highest level of community recognition for exceptional contributions."
    }
  ];

  const pointActivities = [
    {
      activity: "Add a Comment",
      points: 1,
      icon: MessageSquare,
      color: "text-blue-600",
      description: "Share your thoughts and contribute to ongoing discussions"
    },
    {
      activity: "Create a Topic",
      points: 2,
      icon: FileText,
      color: "text-green-600",
      description: "Start new conversations about issues that matter to you"
    },
    {
      activity: "Receive a Like",
      points: 3,
      icon: ThumbsUp,
      color: "text-orange-600",
      description: "When community members appreciate your contributions"
    },
    {
      activity: "Topic Becomes Petition",
      points: 15,
      icon: Target,
      color: "text-purple-600",
      description: "Your topic reaches enough votes to become an official petition"
    }
  ];

  return (
    null
  );
}