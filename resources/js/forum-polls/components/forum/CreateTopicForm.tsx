import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Textarea } from "../ui/textarea";
import { Label } from "../ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { ArrowLeft, Plus } from "lucide-react";

interface CreateTopicFormProps {
  onBack: () => void;
  onCreateTopic: (topic: {
    title: string;
    description: string;
    category: string;
    votesNeeded: number;
  }) => void;
}

export function CreateTopicForm({ onBack, onCreateTopic }: CreateTopicFormProps) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [category, setCategory] = useState("");
  const [votesNeeded, setVotesNeeded] = useState(100);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (title.trim() && description.trim() && category) {
      onCreateTopic({
        title: title.trim(),
        description: description.trim(),
        category,
        votesNeeded
      });
      // Reset form
      setTitle("");
      setDescription("");
      setCategory("");
      setVotesNeeded(100);
    }
  };

  const categories = [
    "Environment",
    "Education",
    "Healthcare",
    "Transportation",
    "Community",
    "Technology",
    "Social Justice",
    "Economic Policy",
    "Other"
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" onClick={onBack}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back
        </Button>
        <h1>Create New Topic</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Start a New Discussion</CardTitle>
          <p className="text-muted-foreground">
            Create a topic for community discussion. Once enough people engage and vote, 
            it can become an official petition.
          </p>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="title">Topic Title</Label>
              <Input
                id="title"
                placeholder="Enter a clear, concise title for your topic"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                placeholder="Provide a detailed description of the issue and your proposed solution..."
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                className="min-h-32"
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="category">Category</Label>
              <Select value={category} onValueChange={setCategory} required>
                <SelectTrigger>
                  <SelectValue placeholder="Select a category" />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((cat) => (
                    <SelectItem key={cat} value={cat.toLowerCase()}>
                      {cat}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="votes">Votes Needed for Petition</Label>
              <Select 
                value={votesNeeded.toString()} 
                onValueChange={(value) => setVotesNeeded(parseInt(value))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="50">50 votes</SelectItem>
                  <SelectItem value="100">100 votes</SelectItem>
                  <SelectItem value="250">250 votes</SelectItem>
                  <SelectItem value="500">500 votes</SelectItem>
                  <SelectItem value="1000">1,000 votes</SelectItem>
                </SelectContent>
              </Select>
              <p className="text-sm text-muted-foreground">
                Number of community votes needed before this becomes an official petition
              </p>
            </div>

            <div className="flex gap-4">
              <Button type="submit" className="flex-1">
                <Plus className="h-4 w-4 mr-2" />
                Create Topic
              </Button>
              <Button type="button" variant="outline" onClick={onBack}>
                Cancel
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}