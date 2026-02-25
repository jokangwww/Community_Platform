import { useState, useRef } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Textarea } from "../ui/textarea";
import { Label } from "../ui/label";
import { Card } from "../ui/card";
import { Badge } from "../ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { X, Upload, Paperclip, Hash } from "lucide-react";
import { ForumCategory } from "./ForumCategoryManager";

interface CreateForumPostProps {
  categories: ForumCategory[];
  onCreatePost: (postData: {
    title: string;
    content: string;
    categoryId: string;
    hashtags: string[];
    attachments: File[];
  }) => void;
  onCancel: () => void;
}

export function CreateForumPost({ categories, onCreatePost, onCancel }: CreateForumPostProps) {
  const [title, setTitle] = useState("");
  const [content, setContent] = useState("");
  const [selectedCategory, setSelectedCategory] = useState<string>("");
  const [hashtags, setHashtags] = useState<string[]>([]);
  const [hashtagInput, setHashtagInput] = useState("");
  const [attachments, setAttachments] = useState<File[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const category = categories.find(c => c.id === selectedCategory);
  const isQA = category?.type === 'academic-qa';

  const handleAddHashtag = (tag: string) => {
    const cleanTag = tag.trim().replace(/^#/, '');
    if (cleanTag && !hashtags.includes(cleanTag)) {
      // Check if category has hashtag restrictions
      if (category?.hashtags && category.hashtags.length > 0) {
        if (category.hashtags.includes(cleanTag)) {
          setHashtags([...hashtags, cleanTag]);
        }
      } else {
        setHashtags([...hashtags, cleanTag]);
      }
    }
    setHashtagInput("");
  };

  const handleRemoveHashtag = (tag: string) => {
    setHashtags(hashtags.filter(t => t !== tag));
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const newFiles = Array.from(e.target.files);
      setAttachments([...attachments, ...newFiles]);
    }
  };

  const handleRemoveAttachment = (index: number) => {
    setAttachments(attachments.filter((_, i) => i !== index));
  };

  const handleSubmit = () => {
    if (!title.trim() || !content.trim() || !selectedCategory) {
      alert("Please fill in all required fields");
      return;
    }

    onCreatePost({
      title: title.trim(),
      content: content.trim(),
      categoryId: selectedCategory,
      hashtags,
      attachments
    });
  };

  // Extract hashtags from content
  const extractHashtagsFromContent = () => {
    const matches = content.match(/#(\w+)/g);
    if (matches) {
      matches.forEach(match => {
        const tag = match.substring(1);
        if (!hashtags.includes(tag)) {
          handleAddHashtag(tag);
        }
      });
    }
  };

  return (
    <Card className="p-6">
      <div className="space-y-6">
        <div>
          <h2 className="text-2xl mb-2">
            {isQA ? 'Ask a Question' : 'Create Discussion'}
          </h2>
          <p className="text-muted-foreground text-sm">
            {isQA 
              ? 'Post your question and get answers from the community'
              : 'Start a conversation with the community'
            }
          </p>
        </div>

        {/* Category selection */}
        <div>
          <Label>Category *</Label>
          <Select value={selectedCategory} onValueChange={setSelectedCategory}>
            <SelectTrigger>
              <SelectValue placeholder="Select a category" />
            </SelectTrigger>
            <SelectContent>
              {categories.map(cat => (
                <SelectItem key={cat.id} value={cat.id}>
                  {cat.name} ({cat.type === 'academic-qa' ? 'Q&A' : 'Discussion'})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {category && (
            <p className="text-xs text-muted-foreground mt-1">
              {category.description}
            </p>
          )}
        </div>

        {/* Title */}
        <div>
          <Label>{isQA ? 'Question Title *' : 'Discussion Title *'}</Label>
          <Input
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder={isQA ? "What's your question?" : "What do you want to discuss?"}
            maxLength={200}
          />
          <p className="text-xs text-muted-foreground mt-1">
            {title.length}/200 characters
          </p>
        </div>

        {/* Content */}
        <div>
          <Label>{isQA ? 'Question Details *' : 'Discussion Content *'}</Label>
          <Textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            onBlur={extractHashtagsFromContent}
            placeholder={
              isQA 
                ? "Provide details about your question. Include any code, context, or examples that might help."
                : "Share your thoughts, ideas, or start a conversation. Use #hashtags to categorize your post."
            }
            rows={8}
          />
          <p className="text-xs text-muted-foreground mt-1">
            Tip: Use @username to mention someone, and #hashtag to add tags
          </p>
        </div>

        {/* Hashtags */}
        <div>
          <Label>Hashtags</Label>
          {category?.hashtags && category.hashtags.length > 0 && (
            <p className="text-xs text-muted-foreground mb-2">
              Suggested tags for this category:
            </p>
          )}
          {category?.hashtags && category.hashtags.length > 0 && (
            <div className="flex gap-2 flex-wrap mb-2">
              {category.hashtags.map(tag => (
                <Button
                  key={tag}
                  size="sm"
                  variant={hashtags.includes(tag) ? "default" : "outline"}
                  onClick={() => {
                    if (hashtags.includes(tag)) {
                      handleRemoveHashtag(tag);
                    } else {
                      handleAddHashtag(tag);
                    }
                  }}
                >
                  #{tag}
                </Button>
              ))}
            </div>
          )}
          <div className="flex gap-2">
            <Input
              value={hashtagInput}
              onChange={(e) => setHashtagInput(e.target.value)}
              onKeyPress={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault();
                  handleAddHashtag(hashtagInput);
                }
              }}
              placeholder="Add custom hashtag and press Enter"
            />
            <Button 
              variant="outline" 
              onClick={() => handleAddHashtag(hashtagInput)}
            >
              <Hash className="h-4 w-4" />
            </Button>
          </div>
          {hashtags.length > 0 && (
            <div className="flex gap-2 flex-wrap mt-2">
              {hashtags.map(tag => (
                <Badge key={tag} variant="secondary" className="flex items-center gap-1">
                  #{tag}
                  <button
                    onClick={() => handleRemoveHashtag(tag)}
                    className="ml-1 hover:text-red-500"
                  >
                    <X className="h-3 w-3" />
                  </button>
                </Badge>
              ))}
            </div>
          )}
        </div>

        {/* File attachments */}
        <div>
          <Label>Attachments (optional)</Label>
          <input
            ref={fileInputRef}
            type="file"
            multiple
            onChange={handleFileSelect}
            className="hidden"
            accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.gif"
          />
          <Button
            variant="outline"
            onClick={() => fileInputRef.current?.click()}
            className="w-full"
          >
            <Upload className="h-4 w-4 mr-2" />
            Upload Files
          </Button>
          <p className="text-xs text-muted-foreground mt-1">
            Supported: PDF, DOC, DOCX, TXT, PNG, JPG, GIF (Max 5MB each)
          </p>
          {attachments.length > 0 && (
            <div className="space-y-2 mt-2">
              {attachments.map((file, index) => (
                <div key={index} className="flex items-center justify-between p-2 bg-muted rounded">
                  <div className="flex items-center gap-2">
                    <Paperclip className="h-4 w-4" />
                    <span className="text-sm">{file.name}</span>
                    <span className="text-xs text-muted-foreground">
                      ({(file.size / 1024).toFixed(1)} KB)
                    </span>
                  </div>
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => handleRemoveAttachment(index)}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Action buttons */}
        <div className="flex gap-3 justify-end">
          <Button variant="outline" onClick={onCancel} size="lg" className="px-8">
            Cancel
          </Button>
          <Button onClick={handleSubmit} size="lg" className="px-8">
            {isQA ? 'Post Question' : 'Create Discussion'}
          </Button>
        </div>
      </div>
    </Card>
  );
}