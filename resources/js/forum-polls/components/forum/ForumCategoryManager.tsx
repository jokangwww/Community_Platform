import { useState } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Textarea } from "../ui/textarea";
import { Card } from "../ui/card";
import { Badge } from "../ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogTrigger } from "../ui/dialog";
import { Label } from "../ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Plus, Edit, Trash2, Tag, BookOpen, MessageSquare, AlertCircle, CheckCircle2 } from "lucide-react";
import { Alert, AlertDescription } from "../ui/alert";

export interface ForumCategory {
  id: string;
  name: string;
  description: string;
  type: 'academic-qa' | 'general-discussion';
  hashtags?: string[];
  postCount: number;
  icon: 'academic' | 'discussion';
}

interface ForumCategoryManagerProps {
  categories: ForumCategory[];
  onCreateCategory: (category: Omit<ForumCategory, 'id' | 'postCount'>) => void;
  onEditCategory: (id: string, category: Partial<ForumCategory>) => void;
  onDeleteCategory: (id: string) => void;
  isAdmin: boolean;
}

export function ForumCategoryManager({
  categories,
  onCreateCategory,
  onEditCategory,
  onDeleteCategory,
  isAdmin
}: ForumCategoryManagerProps) {
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [validationError, setValidationError] = useState<string>("");
  const [successMessage, setSuccessMessage] = useState<string>("");
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    type: "general-discussion" as 'academic-qa' | 'general-discussion',
    hashtags: "",
    icon: "discussion" as 'academic' | 'discussion'
  });

  const resetForm = () => {
    setFormData({
      name: "",
      description: "",
      type: "general-discussion",
      hashtags: "",
      icon: "discussion"
    });
    setEditingId(null);
    setValidationError("");
    setSuccessMessage("");
  };

  const validateCategoryName = (name: string): string | null => {
    const trimmedName = name.trim();
    
    if (!trimmedName) {
      return "Category name is required";
    }
    
    if (trimmedName.length < 3) {
      return "Category name must be at least 3 characters";
    }
    
    if (trimmedName.length > 100) {
      return "Category name must not exceed 100 characters";
    }
    
    // Check for uniqueness (case-insensitive)
    const isDuplicate = categories.some(cat => 
      cat.id !== editingId && 
      cat.name.toLowerCase() === trimmedName.toLowerCase()
    );
    
    if (isDuplicate) {
      return "A category with this name already exists";
    }
    
    return null;
  };

  const handleSubmit = () => {
    // Clear previous messages
    setValidationError("");
    setSuccessMessage("");

    // Validate category name
    const nameError = validateCategoryName(formData.name);
    if (nameError) {
      setValidationError(nameError);
      return;
    }

    // Validate description
    if (!formData.description.trim()) {
      setValidationError("Category description is required");
      return;
    }

    if (formData.description.trim().length < 10) {
      setValidationError("Category description must be at least 10 characters");
      return;
    }

    // Process hashtags
    const processedHashtags = formData.hashtags
      .split(',')
      .map(t => t.trim().toLowerCase().replace(/^#/, ''))
      .filter(Boolean);

    const categoryData = {
      name: formData.name.trim(),
      description: formData.description.trim(),
      type: formData.type,
      hashtags: processedHashtags,
      icon: formData.type === 'academic-qa' ? 'academic' as const : 'discussion' as const
    };

    try {
      if (editingId) {
        onEditCategory(editingId, categoryData);
        setSuccessMessage(`Category "${categoryData.name}" updated successfully!`);
      } else {
        onCreateCategory(categoryData);
        setSuccessMessage(`Category "${categoryData.name}" created successfully!`);
      }

      // Show success message briefly, then close
      setTimeout(() => {
        setIsCreateOpen(false);
        resetForm();
      }, 1500);
    } catch (error) {
      setValidationError("Failed to save category. Please try again.");
    }
  };

  const startEdit = (category: ForumCategory) => {
    setFormData({
      name: category.name,
      description: category.description,
      type: category.type,
      hashtags: category.hashtags?.join(', ') || '',
      icon: category.icon
    });
    setEditingId(category.id);
    setValidationError("");
    setSuccessMessage("");
    setIsCreateOpen(true);
  };

  if (!isAdmin) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {categories.map(category => (
          <Card key={category.id} className="p-4">
            <div className="flex items-start gap-3">
              <div className={`p-2 rounded-lg ${category.type === 'academic-qa' ? 'bg-blue-100' : 'bg-purple-100'}`}>
                {category.type === 'academic-qa' ? (
                  <BookOpen className="h-5 w-5 text-blue-600" />
                ) : (
                  <MessageSquare className="h-5 w-5 text-purple-600" />
                )}
              </div>
              <div className="flex-1">
                <h3 className="font-semibold">{category.name}</h3>
                <p className="text-sm text-muted-foreground">{category.description}</p>
                <div className="flex items-center gap-2 mt-2">
                  <Badge variant={category.type === 'academic-qa' ? 'default' : 'secondary'} className="text-xs">
                    {category.type === 'academic-qa' ? 'Q&A' : 'Discussion'}
                  </Badge>
                  <span className="text-xs text-muted-foreground">{category.postCount} posts</span>
                </div>
              </div>
            </div>
          </Card>
        ))}
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="text-xl">Manage Forum Categories</h2>
        <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
          <DialogTrigger asChild>
            <Button onClick={resetForm} className="cursor-pointer">
              <Plus className="h-4 w-4 mr-2" />
              Add Category
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingId ? 'Edit Category' : 'Create New Category'}</DialogTitle>
              <DialogDescription>
                {editingId ? 'Update the details of your forum category.' : 'Create a new category for organizing forum posts.'}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              {/* Success Message */}
              {successMessage && (
                <Alert className="bg-green-50 border-green-200">
                  <CheckCircle2 className="h-4 w-4 text-green-600" />
                  <AlertDescription className="text-green-800">
                    {successMessage}
                  </AlertDescription>
                </Alert>
              )}

              {/* Validation Error */}
              {validationError && (
                <Alert variant="destructive">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>
                    {validationError}
                  </AlertDescription>
                </Alert>
              )}

              <div>
                <Label className="mb-2">Category Name *</Label>
                <Input
                  value={formData.name}
                  onChange={(e) => {
                    setFormData(prev => ({ ...prev, name: e.target.value }));
                    setValidationError("");
                  }}
                  placeholder="e.g., Computer Science Q&A"
                  maxLength={100}
                  className="cursor-text"
                />
                <p className="text-xs text-muted-foreground mt-1">
                  {formData.name.length}/100 characters
                </p>
              </div>
              <div>
                <Label className="mb-2">Description *</Label>
                <Textarea
                  value={formData.description}
                  onChange={(e) => {
                    setFormData(prev => ({ ...prev, description: e.target.value }));
                    setValidationError("");
                  }}
                  placeholder="Describe this category..."
                  rows={3}
                  maxLength={500}
                  className="cursor-text"
                />
                <p className="text-xs text-muted-foreground mt-1">
                  {formData.description.length}/500 characters
                </p>
              </div>
              <div>
                <Label className="mb-2">Category Type *</Label>
                <Select 
                  value={formData.type} 
                  onValueChange={(value: any) => setFormData(prev => ({ ...prev, type: value }))}
                  className="ml-2"
                >
                  <SelectTrigger className="my-1.25 px-2.5 py-5 cursor-pointer">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="academic-qa" className="cursor-pointer">
                      <div className="flex flex-col items-start">
                        <span className="font-medium">Academic Q&A</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="general-discussion" className="cursor-pointer">
                      <div className="flex flex-col items-start">
                        <span className="font-medium">General Discussion</span>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
                {formData.type === 'academic-qa' && (
                  <Alert className="mt-2 bg-[#e2e2e2]">
                    <BookOpen className="h-4 w-4" />
                    <AlertDescription className="text-sm text-[#000000]">
                      <strong className="text-[#010111]">Academic Q&A Mode:</strong> All posts will be treated as questions with answers that can be voted on and accepted by the author.
                    </AlertDescription>
                  </Alert>
                )}
                {formData.type === 'general-discussion' && (
                  <Alert className="mt-2 bg-[#e2e2e2]">
                    <MessageSquare className="h-4 w-4" />
                    <AlertDescription className="text-sm text-[#000000]">
                      <strong className="text-[#010111]">General Discussion Mode:</strong> Posts use a threaded comment system for open conversations.
                    </AlertDescription>
                  </Alert>
                )}
              </div>
              <div>
                <Label className="mb-2">Allowed Hashtags (optional)</Label>
                <Input
                  value={formData.hashtags}
                  onChange={(e) => setFormData(prev => ({ ...prev, hashtags: e.target.value }))}
                  placeholder="e.g., programming, algorithms, datastructures"
                  className="cursor-text"
                />
                <p className="text-xs text-muted-foreground mt-1">
                  Comma-separated list. Leave empty to allow any hashtags.
                </p>
              </div>
              <div className="flex gap-2">
                <Button onClick={handleSubmit} className="flex-1 cursor-pointer" disabled={!!successMessage}>
                  {editingId ? 'Update Category' : 'Create Category'}
                </Button>
                <Button variant="outline" onClick={() => {
                  setIsCreateOpen(false);
                  resetForm();
                }} className="cursor-pointer">
                  Cancel
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {categories.map(category => (
          <Card key={category.id} className="p-4 hover:shadow-lg transition-shadow">
            <div className="flex items-start gap-3">
              <div className={`p-2 rounded-lg ${category.type === 'academic-qa' ? 'bg-blue-100' : 'bg-purple-100'}`}>
                {category.type === 'academic-qa' ? (
                  <BookOpen className="h-5 w-5 text-blue-600" />
                ) : (
                  <MessageSquare className="h-5 w-5 text-purple-600" />
                )}
              </div>
              <div className="flex-1">
                <h3 className="font-semibold">{category.name}</h3>
                <p className="text-sm text-muted-foreground">{category.description}</p>
                <div className="flex items-center gap-2 mt-2">
                  <Badge variant="default" className="text-xs">
                    {category.type === 'academic-qa' ? 'Q&A' : 'Discussion'}
                  </Badge>
                </div>
                {category.hashtags && category.hashtags.length > 0 && (
                  <div className="flex gap-1 mt-2 flex-wrap">
                    {category.hashtags.map(tag => (
                      <Badge key={tag} variant="outline" className="text-xs">
                        #{tag}
                      </Badge>
                    ))}
                  </div>
                )}
                <div className="flex gap-2 mt-3">
                  <Button 
                    size="sm" 
                    variant="ghost" 
                    onClick={() => startEdit(category)}
                    className="cursor-pointer hover:bg-blue-50"
                  >
                    <Edit className="h-3 w-3" />
                  </Button>
                  <Button 
                    size="sm" 
                    variant="ghost" 
                    onClick={() => onDeleteCategory(category.id)}
                    className="cursor-pointer hover:bg-red-50"
                  >
                    <Trash2 className="h-3 w-3 text-red-500" />
                  </Button>
                </div>
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
}
