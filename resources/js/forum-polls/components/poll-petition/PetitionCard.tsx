import { Badge } from "../ui/badge";
import { Card, CardContent, CardHeader } from "../ui/card";
import { Button } from "../ui/button";
import { Users, Calendar, FileText, TrendingUp, Bookmark } from "lucide-react";

interface Petition {
  id: string;
  title: string;
  description: string;
  proposedSolution: string;
  author: string;
  createdAt: string;
  supportCount: number;
  hasSupported: boolean;
  attachmentCount: number;
  commentCount: number;
}

interface PetitionCardProps {
  petition: Petition;
  onViewPetition: (petitionId: string) => void;
  isBookmarked?: boolean;
  onToggleBookmark?: (petitionId: string) => void;
}

export function PetitionCard({ petition, onViewPetition, isBookmarked = false, onToggleBookmark }: PetitionCardProps) {
  return (
    <Card className="hover:shadow-md transition-shadow">
      <CardHeader className="space-y-3">
        <div className="flex items-start justify-between gap-2">
          <div className="flex-1">
            <div className="flex items-start justify-between gap-2 mb-2">
              <h3 className="leading-snug">{petition.title}</h3>
              {onToggleBookmark && (
                <button
                  onClick={(e) => { e.stopPropagation(); onToggleBookmark(petition.id); }}
                  className="cursor-pointer hover:scale-110 transition-transform flex-shrink-0 mt-0.5"
                  title={isBookmarked ? 'Remove bookmark' : 'Bookmark this petition'}
                >
                  <Bookmark className={`h-5 w-5 ${isBookmarked ? 'text-yellow-500 fill-yellow-500' : 'text-gray-400 hover:text-yellow-500'}`} />
                </button>
              )}
            </div>
            <div className="flex flex-wrap gap-2">
              {petition.hasSupported && (
                <Badge variant="secondary" className="bg-green-100 text-green-700">
                  Supported
                </Badge>
              )}
              {petition.attachmentCount > 0 && (
                <Badge variant="outline">
                  <FileText className="h-3 w-3 mr-1" />
                  {petition.attachmentCount} {petition.attachmentCount === 1 ? 'file' : 'files'}
                </Badge>
              )}
            </div>
          </div>
        </div>
        
        <p className="text-sm text-muted-foreground line-clamp-3">
          {petition.description}
        </p>
      </CardHeader>

      <CardContent className="space-y-4">
        <div className="flex items-center justify-between text-sm">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-1 text-muted-foreground">
              <Calendar className="h-4 w-4" />
              <span>{petition.createdAt}</span>
            </div>
            <div className="flex items-center gap-1 text-muted-foreground">
              <Users className="h-4 w-4" />
              <span>{petition.author}</span>
            </div>
          </div>
        </div>

        <div className="flex items-center justify-between pt-3 border-t">
          <div className="flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-green-600" />
            <div>
              <div className="font-semibold text-lg">{petition.supportCount}</div>
              <div className="text-xs text-muted-foreground">
                {petition.supportCount === 1 ? 'supporter' : 'supporters'}
              </div>
            </div>
          </div>

          <Button 
            onClick={() => onViewPetition(petition.id)} 
            variant={petition.hasSupported ? "outline" : "default"}
          >
            {petition.hasSupported ? "View Details" : "Support Petition"}
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
