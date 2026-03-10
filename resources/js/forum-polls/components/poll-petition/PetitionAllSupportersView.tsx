import { useState } from "react";
import { Button } from "../ui/button";
import { Avatar, AvatarFallback } from "../ui/avatar";
import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  Users,
  MessageSquare,
} from "lucide-react";

interface Supporter {
  id: string;
  nickname: string;
  comment?: string;
  supportedAt: string;
}

interface PetitionAllSupportersViewProps {
  petitionTitle: string;
  supporters: Supporter[];
  onBack: () => void;
}

export function PetitionAllSupportersView({
  petitionTitle,
  supporters,
  onBack,
}: PetitionAllSupportersViewProps) {
  const perPage = 15;
  const [currentPage, setCurrentPage] = useState(1);
  const lastPage = Math.max(1, Math.ceil(supporters.length / perPage));
  const paginatedSupporters = supporters.slice(
    (currentPage - 1) * perPage,
    currentPage * perPage
  );

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <Button
          onClick={onBack}
          variant="ghost"
          className="mb-3 -ml-2 cursor-pointer"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back to Petition
        </Button>
        <h2 className="text-xl font-semibold flex items-center gap-2">
          <Users className="h-5 w-5" />
          All Supporters
        </h2>
        <p className="text-sm text-muted-foreground mt-1">
          {petitionTitle} — {supporters.length}{" "}
          {supporters.length === 1 ? "supporter" : "supporters"}
        </p>
      </div>

      {/* Supporters List */}
      <div className="space-y-2">
        {paginatedSupporters.length === 0 ? (
          <div className="text-center py-16">
            <Users className="h-10 w-10 text-gray-400 mx-auto mb-3" />
            <p className="text-muted-foreground">No supporters yet.</p>
          </div>
        ) : (
          paginatedSupporters.map((supporter, index) => (
            <div
              key={supporter.id}
              className="flex items-start gap-4 p-4 bg-white rounded-lg border hover:shadow-sm transition-shadow"
            >
              <div className="flex items-center gap-3 shrink-0">
                <span className="text-sm font-bold text-muted-foreground w-6 text-right">
                  {(currentPage - 1) * perPage + index + 1}
                </span>
                <Avatar className="h-10 w-10">
                  <AvatarFallback className="text-sm font-medium">
                    {supporter.nickname.charAt(0).toUpperCase()}
                  </AvatarFallback>
                </Avatar>
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <span className="font-medium text-sm">
                    {supporter.nickname}
                  </span>
                  <span className="text-xs text-muted-foreground">
                    {supporter.supportedAt}
                  </span>
                </div>
                {supporter.comment && (
                  <div className="flex items-start gap-1.5 mt-1">
                    <MessageSquare className="h-3.5 w-3.5 text-muted-foreground mt-0.5 shrink-0" />
                    <p className="text-sm text-muted-foreground">
                      {supporter.comment}
                    </p>
                  </div>
                )}
              </div>
            </div>
          ))
        )}
      </div>

      {/* Pagination Controls */}
      <div className="flex items-center justify-between bg-white rounded-xl px-5 py-3 border mt-10">
        <p className="text-muted-foreground text-sm">
          Page {currentPage} of {lastPage} ({supporters.length}{" "}
          {supporters.length === 1 ? "supporter" : "supporters"})
        </p>
        <div className="flex items-center gap-1">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => handlePageChange(currentPage - 1)}
            disabled={currentPage <= 1}
            className="text-muted-foreground hover:text-foreground disabled:opacity-30 cursor-pointer"
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>
          {Array.from({ length: lastPage }, (_, i) => i + 1)
            .filter((page) => {
              if (page === 1 || page === lastPage) return true;
              if (Math.abs(page - currentPage) <= 1) return true;
              return false;
            })
            .reduce<(number | string)[]>((acc, page, idx, arr) => {
              if (idx > 0 && page - (arr[idx - 1] as number) > 1) {
                acc.push("...");
              }
              acc.push(page);
              return acc;
            }, [])
            .map((item, idx) =>
              typeof item === "string" ? (
                <span
                  key={`ellipsis-${idx}`}
                  className="text-muted-foreground px-2"
                >
                  ...
                </span>
              ) : (
                <Button
                  key={item}
                  variant="ghost"
                  size="sm"
                  onClick={() => handlePageChange(item)}
                  className={`min-w-8 h-8 cursor-pointer ${
                    item === currentPage
                      ? "bg-primary text-primary-foreground hover:bg-primary/90"
                      : "text-muted-foreground hover:text-foreground"
                  }`}
                >
                  {item}
                </Button>
              )
            )}
          <Button
            variant="ghost"
            size="sm"
            onClick={() => handlePageChange(currentPage + 1)}
            disabled={currentPage >= lastPage}
            className="text-muted-foreground hover:text-foreground disabled:opacity-30 cursor-pointer"
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}
