import { useState } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Textarea } from "../ui/textarea";
import { Card } from "../ui/card";
import { Badge } from "../ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "../ui/dialog";
import { Label } from "../ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Plus, Edit, Trash2, Tag, BookOpen, MessageSquare } from "lucide-react";

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
  };

  const handleSubmit = () => {
    if (!formData.name.trim()) return;

    const categoryData = {
      name: formData.name.trim(),
      description: formData.description.trim(),
      type: formData.type,
      hashtags: formData.hashtags.split(',').map(t => t.trim()).filter(Boolean),
      icon: formData.type === 'academic-qa' ? 'academic' as const : 'discussion' as const
    };

    if (editingId) {
      onEditCategory(editingId, categoryData);
    } else {
      onCreateCategory(categoryData);
    }

    setIsCreateOpen(false);
    resetForm();
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
            <Button onClick={resetForm}>
              <Plus className="h-4 w-4 mr-2" />
              Add Category
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingId ? 'Edit Category' : 'Create Category'}</DialogTitle>
            </DialogHeader>
            <div className="space-y-4">
              <div>
                <Label>Category Name</Label>
                <Input
                  value={formData.name}
                  onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                  placeholder="e.g., Computer Science Q&A"
                />
              </div>
              <div>
                <Label>Description</Label>
                <Textarea
                  value={formData.description}
                  onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                  placeholder="Describe this category..."
                  rows={3}
                />
              </div>
              <div>
                <Label>Category Type</Label>
                <Select 
                  value={formData.type} 
                  onValueChange={(value: any) => setFormData(prev => ({ ...prev, type: value }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="academic-qa">Academic Q&A (Question/Answer)</SelectItem>
                    <SelectItem value="general-discussion">General Discussion (Threaded)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Allowed Hashtags (comma-separated, optional)</Label>
                <Input
                  value={formData.hashtags}
                  onChange={(e) => setFormData(prev => ({ ...prev, hashtags: e.target.value }))}
                  placeholder="e.g., programming, algorithms, datastructures"
                />
              </div>
              <div className="flex gap-2">
                <Button onClick={handleSubmit} className="flex-1">
                  {editingId ? 'Update' : 'Create'}
                </Button>
                <Button variant="outline" onClick={() => setIsCreateOpen(false)}>
                  Cancel
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>

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
                  <Button size="sm" variant="ghost" onClick={() => startEdit(category)}>
                    <Edit className="h-3 w-3" />
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => onDeleteCategory(category.id)}>
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
