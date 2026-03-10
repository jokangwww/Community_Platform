import { useState } from "react";
import { Button } from "../ui/button";
import { Badge } from "../ui/badge";
import { 
  ArrowLeft, 
  ArrowRight,
  Hash,
  ChevronLeft,
  ChevronRight,
} from "lucide-react";

// Tag colors for cycling through
const tagColors = [
  "bg-blue-500",
  "bg-orange-500",
  "bg-green-500",
  "bg-amber-600",
  "bg-purple-500",
  "bg-pink-500",
  "bg-teal-500",
  "bg-red-500",
];

interface Tag {
  id: string;
  name: string;
  postCount: number;
}

interface ForumAllTagsViewProps {
  tags: Tag[];
  onBack: () => void;
  onTagClick: (tagName: string) => void;
}

export function ForumAllTagsView({
  tags,
  onBack,
  onTagClick,
}: ForumAllTagsViewProps) {
  const perPage = 15;
  const [currentPage, setCurrentPage] = useState(1);
  const lastPage = Math.max(1, Math.ceil(tags.length / perPage));
  const paginatedTags = tags.slice((currentPage - 1) * perPage, currentPage * perPage);

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <div className="min-h-screen bg-[#e8e8ea]">
      <div className="max-w-5xl mx-auto px-6 py-6">
        {/* Back Button */}
        <Button
          variant="ghost"
          onClick={onBack}
          className="mb-4 text-gray-700 hover:text-gray-900 hover:bg-gray-300 cursor-pointer"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back to Forum
        </Button>

        {/* Header */}
        <div className="bg-[#2c3138] rounded-xl p-6 mb-6">
          <div className="flex items-center gap-4">
            <div className="bg-[#ff6934] w-16 h-16 rounded-xl flex items-center justify-center">
              <Hash className="h-8 w-8 text-white" />
            </div>
            <div>
              <h1 className="text-white text-3xl font-semibold">All Hashtags</h1>
              <p className="text-gray-400 mt-1">{tags.length} {tags.length === 1 ? 'hashtag' : 'hashtags'} found</p>
            </div>
          </div>
        </div>

        {/* Tags List */}
        <div className="space-y-3">
          {paginatedTags.length === 0 ? (
            <div className="bg-[#2c3138] rounded-xl p-12 text-center">
              <Hash className="h-16 w-16 text-gray-600 mx-auto mb-4" />
              <h3 className="text-white text-xl mb-2">No hashtags found</h3>
              <p className="text-gray-400">There are no hashtags yet.</p>
            </div>
          ) : (
            paginatedTags.map((tag, idx) => {
              const globalIndex = (currentPage - 1) * perPage + idx;
              return (
                <button
                  key={tag.id}
                  onClick={() => onTagClick(tag.name)}
                  className="w-full bg-[#2c3138] rounded-xl p-5 hover:bg-[#333a42] transition-colors cursor-pointer flex items-center gap-4"
                >
                  <div className={`${globalIndex < 4 ? tagColors[globalIndex] : 'bg-[#4b5563]'} w-12 h-12 rounded-xl flex items-center justify-center text-white text-lg font-bold shrink-0`}>
                    {globalIndex + 1}
                  </div>
                  <div className="flex flex-col items-start text-left flex-1 min-w-0">
                    <span className="text-white text-lg font-medium">#{tag.name}</span>
                    <span className="text-gray-400 text-sm">{tag.postCount} {tag.postCount === 1 ? 'post' : 'posts'}</span>
                  </div>
                  <ArrowRight className="h-5 w-5 text-gray-500 shrink-0" />
                </button>
              );
            })
          )}
        </div>

        {/* Pagination Controls - always visible */}
        <div className="flex items-center justify-between bg-[#2c3138] rounded-xl px-5 py-3" style={{ marginTop: '2.5rem' }}>
          <p className="text-gray-400 text-sm">
            Page {currentPage} of {lastPage} ({tags.length} {tags.length === 1 ? 'hashtag' : 'hashtags'})
          </p>
            <div className="flex items-center gap-1">
              <Button
                variant="ghost"
                size="sm"
                onClick={() => handlePageChange(currentPage - 1)}
                disabled={currentPage <= 1}
                className="text-gray-400 hover:text-white disabled:opacity-30 cursor-pointer"
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>
              {Array.from({ length: lastPage }, (_, i) => i + 1)
                .filter(page => {
                  if (page === 1 || page === lastPage) return true;
                  if (Math.abs(page - currentPage) <= 1) return true;
                  return false;
                })
                .reduce<(number | string)[]>((acc, page, idx, arr) => {
                  if (idx > 0 && page - (arr[idx - 1] as number) > 1) {
                    acc.push('...');
                  }
                  acc.push(page);
                  return acc;
                }, [])
                .map((item, idx) =>
                  typeof item === 'string' ? (
                    <span key={`ellipsis-${idx}`} className="text-gray-500 px-2">...</span>
                  ) : (
                    <Button
                      key={item}
                      variant="ghost"
                      size="sm"
                      onClick={() => handlePageChange(item)}
                      className={`min-w-8 h-8 cursor-pointer ${
                        item === currentPage
                          ? 'bg-[#ff6934] text-white hover:bg-[#ff7a47]'
                          : 'text-gray-400 hover:text-white hover:bg-[#3a4149]'
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
                className="text-gray-400 hover:text-white disabled:opacity-30 cursor-pointer"
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
      </div>
    </div>
  );
}
