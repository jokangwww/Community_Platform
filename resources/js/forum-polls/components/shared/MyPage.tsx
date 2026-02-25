import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Label } from "../ui/label";
import { Badge } from "../ui/badge";
import { Avatar, AvatarImage, AvatarFallback } from "../ui/avatar";
import { Separator } from "../ui/separator";
import { Progress } from "../ui/progress";
import { 
  User, 
  Edit3, 
  MessageSquare, 
  ThumbsUp, 
  FileText, 
  Trophy, 
  Star,
  Award,
  Crown,
  Sparkles
} from "lucide-react";

export interface UserProfile {
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

export interface BadgeLevel {
  id: number;
  name: string;
  icon: React.ComponentType<any>;
  color: string;
  minPoints: number;
  maxPoints: number;
}

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

interface MyPageProps {
  userProfile: UserProfile;
  onUpdateProfile: (nickname: string) => void;
  onBack: () => void;
}

export function MyPage({ userProfile, onUpdateProfile, onBack }: MyPageProps) {
  const [isEditing, setIsEditing] = useState(false);
  const [nickname, setNickname] = useState(userProfile.nickname);

  const handleSave = () => {
    if (nickname.trim()) {
      onUpdateProfile(nickname.trim());
      setIsEditing(false);
    }
  };

  const handleCancel = () => {
    setNickname(userProfile.nickname);
    setIsEditing(false);
  };

  const calculateLevel = (points: number): BadgeLevel => {
    return badgeLevels.find(level => points >= level.minPoints && points <= level.maxPoints) || badgeLevels[0];
  };

  const getNextLevel = (currentLevel: BadgeLevel): BadgeLevel | null => {
    const nextLevelId = currentLevel.id + 1;
    return badgeLevels.find(level => level.id === nextLevelId) || null;
  };

  const calculateProgress = (points: number, currentLevel: BadgeLevel): number => {
    if (currentLevel.id === 5) return 100; // Max level
    const nextLevel = getNextLevel(currentLevel);
    if (!nextLevel) return 100;
    
    const progressInLevel = points - currentLevel.minPoints;
    const levelRange = nextLevel.minPoints - currentLevel.minPoints;
    return Math.min((progressInLevel / levelRange) * 100, 100);
  };

  const currentLevel = calculateLevel(userProfile.activityPoints);
  const nextLevel = getNextLevel(currentLevel);
  const progress = calculateProgress(userProfile.activityPoints, currentLevel);
  const pointsToNextLevel = nextLevel ? nextLevel.minPoints - userProfile.activityPoints : 0;

  const IconComponent = currentLevel.icon;

  return (
    null
  );
}