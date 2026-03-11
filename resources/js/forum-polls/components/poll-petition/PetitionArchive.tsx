import { useState } from "react";
import { Card, CardContent, CardHeader } from "../ui/card";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Badge } from "../ui/badge";
import { Progress } from "../ui/progress";
import { Search, TrendingUp, ArrowLeft, Calendar, Users, FileText } from "lucide-react";

interface ArchivedPetition {
  id: string;
  title: string;
  description: string;
  proposedSolution: string;
  author: string;
  createdAt: string;
  supportCount: number;
  goal: number;
  status: string;
  hasSupported: boolean;
  attachmentCount: number;
  commentCount: number;
}

interface PetitionArchiveProps {
  archivedPetitions: ArchivedPetition[];
  onBack: () => void;
  onViewPetition: (petitionId: string) => void;
}

export function PetitionArchive({ archivedPetitions, onBack, onViewPetition }: PetitionArchiveProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [sortBy, setSortBy] = useState("date");

  const getStatusColor = (status: string) => {
    const colors: { [key: string]: string } = {
      closed: "bg-blue-100 text-blue-800",
      disabled: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
  };

  const getStatusLabel = (status: string) => {
    const labels: { [key: string]: string } = {
      closed: "Closed",
      disabled: "Archived",
    };
    return labels[status] || status.charAt(0).toUpperCase() + status.slice(1);
  };

  const filteredPetitions = archivedPetitions.filter(petition => {
    return petition.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
           petition.description.toLowerCase().includes(searchTerm.toLowerCase());
  });

  const sortedPetitions = [...filteredPetitions].sort((a, b) => {
    switch (sortBy) {
      case "popularity":
        return b.supportCount - a.supportCount;
      case "date":
      default:
        return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime();
    }
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <Button onClick={onBack} variant="ghost" className="mb-3 -ml-2 cursor-pointer">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back to Petitions
          </Button>
          <h1>Petition Archive</h1>
          <p className="text-muted-foreground">
            Browse past petitions and their outcomes
          </p>
        </div>
      </div>

      <div className="flex flex-wrap gap-4 items-center">
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
          <Input
            placeholder="Search archived petitions..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>

        <Select value={sortBy} onValueChange={setSortBy}>
          <SelectTrigger className="w-48">
            <TrendingUp className="h-4 w-4 mr-2" />
            <SelectValue placeholder="Sort by" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="date">Most Recent</SelectItem>
            <SelectItem value="popularity">Most Supporters</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="grid grid-cols-1 gap-4">
        {sortedPetitions.length === 0 ? (
          <Card>
            <CardContent className="py-12 text-center">
              <p className="text-muted-foreground">
                {searchTerm
                  ? "No archived petitions match your search."
                  : "No archived petitions yet."}
              </p>
            </CardContent>
          </Card>
        ) : (
          sortedPetitions.map((petition) => {
            const progress = petition.goal > 0
              ? Math.min(Math.round((petition.supportCount / petition.goal) * 100), 100)
              : 0;
            const goalReached = petition.supportCount >= petition.goal;

            return (
              <Card key={petition.id} className="hover:shadow-md transition-shadow">
                <CardHeader>
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <h3 className="text-lg">{petition.title}</h3>
                      </div>
                      <div className="flex flex-wrap gap-2 mb-2">
                        <Badge className={getStatusColor(petition.status)}>
                          {getStatusLabel(petition.status)}
                        </Badge>
                        {goalReached && (
                          <Badge className="bg-green-100 text-green-800">
                            Goal Reached
                          </Badge>
                        )}
                      </div>
                      <p className="text-sm text-muted-foreground">{petition.description}</p>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="flex items-center gap-1 text-sm text-muted-foreground mb-1">
                        <Users className="h-4 w-4" />
                        <span>{petition.supportCount} / {petition.goal} supporters</span>
                      </div>
                      <div className="flex items-center gap-1 text-sm text-muted-foreground mb-1">
                        <Calendar className="h-4 w-4" />
                        <span>{petition.createdAt}</span>
                      </div>
                      {petition.attachmentCount > 0 && (
                        <div className="flex items-center gap-1 text-sm text-muted-foreground">
                          <FileText className="h-4 w-4" />
                          <span>{petition.attachmentCount} attachment(s)</span>
                        </div>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  {/* Supporter progress bar */}
                  <div className="space-y-1">
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Supporter Progress</span>
                      <span className={`font-medium ${goalReached ? 'text-green-600' : 'text-orange-600'}`}>
                        {progress}%
                      </span>
                    </div>
                    <Progress value={progress} className="h-2" />
                  </div>

                  {/* Proposed solution preview */}
                  {petition.proposedSolution && (
                    <div className="bg-gray-50 rounded-lg p-3">
                      <p className="text-xs font-medium text-muted-foreground mb-1">Proposed Solution</p>
                      <p className="text-sm text-gray-700 line-clamp-2">{petition.proposedSolution}</p>
                    </div>
                  )}

                  <div className="flex items-center justify-between pt-3 border-t">
                    <div className="flex items-center gap-4 text-sm text-muted-foreground">
                      <span>By {petition.author}</span>
                      {petition.commentCount > 0 && (
                        <span>{petition.commentCount} comment(s)</span>
                      )}
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => onViewPetition(petition.id)}
                      className="cursor-pointer"
                    >
                      <FileText className="h-4 w-4 mr-2" />
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
