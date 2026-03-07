import { useState } from "react";
import { Button } from "../ui/button";
import { Input } from "../ui/input";
import { Label } from "../ui/label";
import { Textarea } from "../ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Badge } from "../ui/badge";
import { ArrowLeft, Plus, X, Calendar, Users } from "lucide-react";

interface CreatePollFormProps {
  onBack: () => void;
  onCreatePoll: (poll: {
    title: string;
    description: string;
    options: string[];
    expiryDate: string;
    category: string;
    targetCriteria: {
      faculty?: string;
      yearOfStudy?: string;
      course?: string;
    };
  }) => void;
  canCreatePoll: boolean;
  nextAvailableDate?: string;
}

export function CreatePollForm({ onBack, onCreatePoll, canCreatePoll, nextAvailableDate }: CreatePollFormProps) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [options, setOptions] = useState(["", ""]);
  const [expiryDate, setExpiryDate] = useState("");
  const [category, setCategory] = useState("");
  const [targetFaculty, setTargetFaculty] = useState("all");
  const [targetYear, setTargetYear] = useState("all");
  const [targetCourse, setTargetCourse] = useState("all");

  const categories = ["Campus Life", "Facilities", "Academic", "Events", "Sports", "Student Services"];
  const faculties = ["All Students", "Engineering", "Business", "Arts & Sciences", "Medicine", "Law"];
  const years = ["All Years", "1st Year", "2nd Year", "3rd Year", "4th Year", "Graduate"];
  const courses = ["All Courses", "Computer Science", "Business Administration", "Biology", "Psychology", "Economics"];

  const handleAddOption = () => {
    if (options.length < 5) {
      setOptions([...options, ""]);
    }
  };

  const handleRemoveOption = (index: number) => {
    if (options.length > 2) {
      const newOptions = options.filter((_, i) => i !== index);
      setOptions(newOptions);
    }
  };

  const handleOptionChange = (index: number, value: string) => {
    const newOptions = [...options];
    newOptions[index] = value;
    setOptions(newOptions);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    const validOptions = options.filter(opt => opt.trim() !== "");
    
    if (!canCreatePoll) {
      return;
    }

    if (title.trim() && description.trim() && validOptions.length >= 2 && expiryDate && category) {
      onCreatePoll({
        title: title.trim(),
        description: description.trim(),
        options: validOptions,
        expiryDate,
        category,
        targetCriteria: {
          faculty: targetFaculty !== "all" ? targetFaculty : undefined,
          yearOfStudy: targetYear !== "all" ? targetYear : undefined,
          course: targetCourse !== "all" ? targetCourse : undefined,
        }
      });
    }
  };

  const isFormValid = () => {
    const validOptions = options.filter(opt => opt.trim() !== "");
    return title.trim() && description.trim() && validOptions.length >= 2 && expiryDate && category;
  };

  const getTodayDate = () => {
    const today = new Date();
    today.setDate(today.getDate() + 1); // Minimum 1 day from now
    return today.toISOString().split('T')[0];
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1>Create New Poll</h1>
          <p className="text-muted-foreground">
            Create a poll to gather opinions from the student community
          </p>
        </div>
        <Button onClick={onBack} variant="outline">
          <ArrowLeft className="h-4 w-4 mr-2 cursor-pointer" />
          Back
        </Button>
      </div>

      {!canCreatePoll && nextAvailableDate && (
        <Card className="border-yellow-200 bg-yellow-50 cursor-pointer">
          <CardContent className="pt-6 cursor-pointer">
            <div className="flex items-start gap-3 cursor-pointer">
              <Calendar className="h-5 w-5 text-yellow-600 mt-0.5 cursor-pointer" />
              <div>
                <h3 className="text-yellow-900 mb-1 cursor-pointer">Poll Creation Limit Reached</h3>
                <p className="text-yellow-800 text-sm cursor-pointer">
                  You can only create one poll every 7 days. You'll be able to create your next poll on{" "}
                  <strong>{nextAvailableDate}</strong>.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <form onSubmit={handleSubmit} className="space-y-6 cursor-pointer">
        <Card>
          <CardHeader>
            <CardTitle>Poll Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4 cursor-pointer">
            <div className="space-y-2 cursor-pointer">
              <Label htmlFor="title">Poll Title *</Label>
              <Input
                id="title"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="e.g., What time should library hours be extended to?"
                maxLength={100}
                required
                disabled={!canCreatePoll}
              />
              <p className="text-xs text-muted-foreground cursor-pointer">
                {title.length}/100 characters
              </p>
            </div>

            <div className="space-y-2 cursor-pointer">
              <Label htmlFor="description">Description *</Label>
              <Textarea
                id="description"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Provide context and details for your poll..."
                rows={4}
                maxLength={500}
                required
                disabled={!canCreatePoll}
              />
              <p className="text-xs text-muted-foreground cursor-pointer">
                {description.length}/500 characters
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 cursor-pointer">
              <div className="space-y-2 cursor-pointer">
                <Label htmlFor="category">Category *</Label>
                <Select value={category} onValueChange={setCategory} disabled={!canCreatePoll}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map((cat) => (
                      <SelectItem key={cat} value={cat.toLowerCase().replace(/ /g, '-')}>
                        {cat}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2 cursor-pointer">
                <Label htmlFor="expiryDate">Expiry Date *</Label>
                <Input
                  id="expiryDate"
                  type="date"
                  value={expiryDate}
                  onChange={(e) => setExpiryDate(e.target.value)}
                  min={getTodayDate()}
                  required
                  disabled={!canCreatePoll}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Poll Options</CardTitle>
            <p className="text-sm text-muted-foreground cursor-pointer">
              Add 2-5 options for students to choose from
            </p>
          </CardHeader>
          <CardContent className="space-y-3 cursor-pointer">
            {options.map((option, index) => (
              <div key={index} className="flex gap-2 cursor-pointer">
                <div className="flex-1 cursor-pointer">
                  <Label htmlFor={`option-${index}`} className="sr-only cursor-pointer">
                    Option {index + 1}
                  </Label>
                  <Input
                    id={`option-${index}`}
                    value={option}
                    onChange={(e) => handleOptionChange(index, e.target.value)}
                    placeholder={`Option ${index + 1}`}
                    maxLength={100}
                    required={index < 2}
                    disabled={!canCreatePoll}
                  />
                </div>
                {options.length > 2 && (
                  <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={() => handleRemoveOption(index)}
                    disabled={!canCreatePoll}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>
            ))}
            
            {options.length < 5 && (
              <Button
                type="button"
                variant="outline"
                onClick={handleAddOption}
                className="w-full cursor-pointer"
                disabled={!canCreatePoll}
              >
                <Plus className="h-4 w-4 mr-2" />
                Add Option ({options.length}/5)
              </Button>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Users className="h-5 w-5" />
              Target Criteria (Optional)
            </CardTitle>
            <p className="text-sm text-muted-foreground">
              Limit poll visibility to specific student groups
            </p>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="faculty">Faculty</Label>
              <Select value={targetFaculty} onValueChange={setTargetFaculty} disabled={!canCreatePoll}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {faculties.map((faculty) => (
                    <SelectItem key={faculty} value={faculty === "All Students" ? "all" : faculty.toLowerCase()}>
                      {faculty}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="year">Year of Study</Label>
              <Select value={targetYear} onValueChange={setTargetYear} disabled={!canCreatePoll}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {years.map((year) => (
                    <SelectItem key={year} value={year === "All Years" ? "all" : year.toLowerCase()}>
                      {year}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="course">Course</Label>
              <Select value={targetCourse} onValueChange={setTargetCourse} disabled={!canCreatePoll}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {courses.map((course) => (
                    <SelectItem key={course} value={course === "All Courses" ? "all" : course.toLowerCase()}>
                      {course}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {(targetFaculty !== "all" || targetYear !== "all" || targetCourse !== "all") && (
              <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p className="text-sm text-blue-900 mb-2">Target Audience:</p>
                <div className="flex flex-wrap gap-2">
                  {targetFaculty !== "all" && (
                    <Badge className="bg-blue-100 text-blue-800">
                      {faculties.find(f => f.toLowerCase() === targetFaculty)}
                    </Badge>
                  )}
                  {targetYear !== "all" && (
                    <Badge className="bg-blue-100 text-blue-800">
                      {years.find(y => y.toLowerCase() === targetYear)}
                    </Badge>
                  )}
                  {targetCourse !== "all" && (
                    <Badge className="bg-blue-100 text-blue-800">
                      {courses.find(c => c.toLowerCase() === targetCourse)}
                    </Badge>
                  )}
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <div className="flex justify-end gap-3">
          <Button type="button" variant="outline" onClick={onBack}>
            Cancel
          </Button>
          <Button type="submit" disabled={!isFormValid() || !canCreatePoll}>
            Create Poll
          </Button>
        </div>
      </form>
    </div>
  );
}
