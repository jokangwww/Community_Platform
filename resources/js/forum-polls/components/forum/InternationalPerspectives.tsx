import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../ui/card";
import { Button } from "../ui/button";
import { Textarea } from "../ui/textarea";
import { Input } from "../ui/input";
import { Label } from "../ui/label";
import { Badge } from "../ui/badge";
import { Avatar, AvatarImage, AvatarFallback } from "../ui/avatar";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../ui/select";
import { Separator } from "../ui/separator";
import { Globe, MapPin, FileText, Plus, ExternalLink, Bot, Sparkles, RefreshCw } from "lucide-react";

interface InternationalPerspective {
  id: string;
  country: string;
  region: string;
  author: string;
  title: string;
  content: string;
  regulationType: 'law' | 'policy' | 'case-study' | 'experience';
  timestamp: string;
  source?: string;
  isAIGenerated?: boolean;
}

interface InternationalPerspectivesProps {
  topicId: string;
  topicTitle: string;
}

export function InternationalPerspectives({ topicId, topicTitle }: InternationalPerspectivesProps) {
  const [perspectives, setPerspectives] = useState<InternationalPerspective[]>(mockPerspectives);
  const [showForm, setShowForm] = useState(false);
  const [isGeneratingAI, setIsGeneratingAI] = useState(false);
  const [aiPerspectives, setAiPerspectives] = useState<InternationalPerspective[]>([]);
  const [newPerspective, setNewPerspective] = useState({
    country: "",
    region: "",
    title: "",
    content: "",
    regulationType: "experience" as const,
    source: ""
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (newPerspective.title.trim() && newPerspective.content.trim() && newPerspective.country) {
      const perspective: InternationalPerspective = {
        id: Date.now().toString(),
        ...newPerspective,
        author: "Current User", // In real app, from auth
        timestamp: "Just now"
      };
      setPerspectives([perspective, ...perspectives]);
      setNewPerspective({
        country: "",
        region: "",
        title: "",
        content: "",
        regulationType: "experience",
        source: ""
      });
      setShowForm(false);
    }
  };

  const getRegulationTypeColor = (type: string) => {
    switch (type) {
      case 'law': return 'bg-red-100 text-red-800';
      case 'policy': return 'bg-blue-100 text-blue-800';
      case 'case-study': return 'bg-green-100 text-green-800';
      case 'experience': return 'bg-purple-100 text-purple-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getRegulationTypeText = (type: string) => {
    switch (type) {
      case 'law': return 'Law/Regulation';
      case 'policy': return 'Policy';
      case 'case-study': return 'Case Study';
      case 'experience': return 'Experience';
      default: return type;
    }
  };

  const countries = [
    "United States", "Canada", "United Kingdom", "Germany", "France", "Netherlands",
    "Sweden", "Denmark", "Norway", "Australia", "New Zealand", "Japan", "South Korea",
    "Singapore", "Switzerland", "Austria", "Belgium", "Spain", "Italy", "Other"
  ];

  const generateAIPerspectives = async () => {
    setIsGeneratingAI(true);
    
    // Simulate AI generation with relevant perspectives based on topic
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    const aiGeneratedPerspectives = getAIGeneratedPerspectives(topicTitle);
    setAiPerspectives(aiGeneratedPerspectives);
    setIsGeneratingAI(false);
  };

  const addAIPerspectiveToMain = (aiPerspective: InternationalPerspective) => {
    const newPerspective = {
      ...aiPerspective,
      id: Date.now().toString(),
      isAIGenerated: true
    };
    setPerspectives([newPerspective, ...perspectives]);
    setAiPerspectives(prev => prev.filter(p => p.id !== aiPerspective.id));
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="flex items-center gap-2">
            <Globe className="h-5 w-5" />
            International Perspectives
          </h3>
          <p className="text-sm text-muted-foreground">
            Learn from regulations, policies, and experiences from around the world
          </p>
        </div>
        <div className="flex gap-2">
          <Button onClick={generateAIPerspectives} variant="outline" disabled={isGeneratingAI}>
            {isGeneratingAI ? (
              <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
            ) : (
              <Bot className="h-4 w-4 mr-2" />
            )}
            {isGeneratingAI ? "Generating..." : "AI Suggestions"}
          </Button>
          <Button onClick={() => setShowForm(!showForm)} variant="outline">
            <Plus className="h-4 w-4 mr-2" />
            Share Perspective
          </Button>
        </div>
      </div>

      {showForm && (
        <Card>
          <CardHeader>
            <CardTitle>Share International Perspective</CardTitle>
            <p className="text-sm text-muted-foreground">
              Help others learn from your country's approach to this topic
            </p>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="country">Country</Label>
                  <Select value={newPerspective.country} onValueChange={(value) => 
                    setNewPerspective(prev => ({...prev, country: value}))
                  }>
                    <SelectTrigger>
                      <SelectValue placeholder="Select country" />
                    </SelectTrigger>
                    <SelectContent>
                      {countries.map(country => (
                        <SelectItem key={country} value={country}>
                          {country}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="region">Region/State (Optional)</Label>
                  <Input
                    placeholder="e.g., California, Bavaria, Ontario"
                    value={newPerspective.region}
                    onChange={(e) => setNewPerspective(prev => ({...prev, region: e.target.value}))}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="regulationType">Type</Label>
                <Select value={newPerspective.regulationType} onValueChange={(value: any) => 
                  setNewPerspective(prev => ({...prev, regulationType: value}))
                }>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="law">Law/Regulation</SelectItem>
                    <SelectItem value="policy">Policy</SelectItem>
                    <SelectItem value="case-study">Case Study</SelectItem>
                    <SelectItem value="experience">Personal Experience</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="title">Title</Label>
                <Input
                  placeholder="Brief title describing the perspective"
                  value={newPerspective.title}
                  onChange={(e) => setNewPerspective(prev => ({...prev, title: e.target.value}))}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="content">Content</Label>
                <Textarea
                  placeholder="Describe the regulation, policy, or experience in detail..."
                  value={newPerspective.content}
                  onChange={(e) => setNewPerspective(prev => ({...prev, content: e.target.value}))}
                  className="min-h-24"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="source">Source/Reference (Optional)</Label>
                <Input
                  placeholder="Link to official document or reference"
                  value={newPerspective.source}
                  onChange={(e) => setNewPerspective(prev => ({...prev, source: e.target.value}))}
                />
              </div>

              <div className="flex gap-2">
                <Button type="submit">
                  Share Perspective
                </Button>
                <Button type="button" variant="outline" onClick={() => setShowForm(false)}>
                  Cancel
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {aiPerspectives.length > 0 && (
        <Card className="border-blue-200 bg-blue-50/50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-blue-900">
              <Sparkles className="h-5 w-5 text-blue-600" />
              AI-Generated International Perspectives
            </CardTitle>
            <p className="text-sm text-blue-700">
              AI has found relevant international approaches to "{topicTitle}". Click "Add to Discussion" to include any perspective in the main conversation.
            </p>
          </CardHeader>
          <CardContent className="space-y-4">
            {aiPerspectives.map(perspective => (
              <div key={perspective.id} className="bg-white rounded-lg p-4 border border-blue-200">
                <div className="flex items-start justify-between mb-3">
                  <div className="space-y-2">
                    <div className="flex items-center gap-2">
                      <Badge className={getRegulationTypeColor(perspective.regulationType)}>
                        {getRegulationTypeText(perspective.regulationType)}
                      </Badge>
                      <div className="flex items-center gap-1 text-sm text-muted-foreground">
                        <MapPin className="h-3 w-3" />
                        {perspective.country}
                        {perspective.region && `, ${perspective.region}`}
                      </div>
                    </div>
                    <h4 className="font-medium text-blue-900">{perspective.title}</h4>
                  </div>
                  <Button 
                    size="sm" 
                    onClick={() => addAIPerspectiveToMain(perspective)}
                    className="bg-blue-600 hover:bg-blue-700"
                  >
                    Add to Discussion
                  </Button>
                </div>
                <p className="text-sm text-gray-700 mb-3">{perspective.content}</p>
                {perspective.source && (
                  <a 
                    href={perspective.source} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1"
                  >
                    <ExternalLink className="h-3 w-3" />
                    View Source
                  </a>
                )}
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      <div className="space-y-4">
        {perspectives.map(perspective => (
          <Card key={perspective.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="space-y-2">
                  <div className="flex items-center gap-2">
                    <Badge className={getRegulationTypeColor(perspective.regulationType)}>
                      {getRegulationTypeText(perspective.regulationType)}
                    </Badge>
                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                      <MapPin className="h-3 w-3" />
                      {perspective.country}
                      {perspective.region && `, ${perspective.region}`}
                    </div>
                  </div>
                  <CardTitle className="text-lg">{perspective.title}</CardTitle>
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-sm">{perspective.content}</p>
              
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Avatar className="h-6 w-6">
                    <AvatarFallback className="text-xs">
                      {perspective.isAIGenerated ? <Bot className="h-3 w-3" /> : perspective.author.charAt(0)}
                    </AvatarFallback>
                  </Avatar>
                  <div className="text-xs text-muted-foreground flex items-center gap-2">
                    <span>{perspective.isAIGenerated ? "AI Generated" : perspective.author} • {perspective.timestamp}</span>
                    {perspective.isAIGenerated && (
                      <Badge variant="secondary" className="text-xs bg-blue-100 text-blue-700">
                        AI
                      </Badge>
                    )}
                  </div>
                </div>
                
                {perspective.source && (
                  <Button variant="ghost" size="sm" asChild>
                    <a href={perspective.source} target="_blank" rel="noopener noreferrer">
                      <ExternalLink className="h-3 w-3 mr-1" />
                      Source
                    </a>
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
        
        {perspectives.length === 0 && (
          <div className="text-center py-8 text-muted-foreground">
            <Globe className="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p>No international perspectives shared yet.</p>
            <p className="text-sm">Be the first to share how your country handles this topic!</p>
          </div>
        )}
      </div>
    </div>
  );
}

// Mock data for international perspectives
const mockPerspectives: InternationalPerspective[] = [
  {
    id: "1",
    country: "Netherlands",
    region: "Amsterdam",
    author: "Lars van der Berg",
    title: "Comprehensive Cycling Infrastructure Network",
    content: "In the Netherlands, we have extensive bike lane networks that are completely separated from car traffic. Our infrastructure includes dedicated traffic lights for cyclists, bike parking facilities, and integration with public transport. The result is that 27% of all trips are made by bicycle. Key factors include: protected bike lanes with physical barriers, priority at intersections, and maintenance during winter months.",
    regulationType: "policy",
    timestamp: "2 hours ago",
    source: "https://www.government.nl/topics/bicycle-policy"
  },
  {
    id: "2", 
    country: "Denmark",
    region: "Copenhagen",
    author: "Anna Pedersen",
    title: "Copenhagen's Bike Lane Success Story",
    content: "Copenhagen has invested heavily in cycling infrastructure since the 1970s. We now have over 390km of bike lanes and 42% of citizens cycle to work daily. The city's approach includes: separated bike lanes on major roads, bike traffic lights timed for cycling speed (20 km/h), heated bike paths in winter, and bike-friendly urban planning. The economic benefit is estimated at €230 million annually in health and reduced congestion costs.",
    regulationType: "case-study",
    timestamp: "5 hours ago",
    source: "https://urbandevelopmentcph.kk.dk/artikel/copenhagens-bicycle-strategy-2011-2025"
  }
];

// Function to generate AI perspectives based on topic
function getAIGeneratedPerspectives(topicTitle: string): InternationalPerspective[] {
  const topicLower = topicTitle.toLowerCase();
  
  if (topicLower.includes('bike') || topicLower.includes('cycling') || topicLower.includes('transport')) {
    return [
      {
        id: "ai-1",
        country: "Germany",
        region: "Berlin",
        author: "AI Assistant",
        title: "German Federal Cycling Infrastructure Investment",
        content: "Germany's National Cycling Plan 2020 allocated €1.46 billion for cycling infrastructure development. The plan focuses on creating a nationwide network of cycle highways connecting major cities. Key elements include: federal funding for intercity cycling routes, standardized cycling infrastructure guidelines, and integration with public transport systems. The program has resulted in a 20% increase in cycling trips nationwide and significant reductions in urban air pollution.",
        regulationType: "policy",
        timestamp: "Generated now",
        source: "https://www.bmvi.de/SharedDocs/EN/Documents/VerkehrUndMobilitaet/national-cycling-plan-2020.pdf",
        isAIGenerated: true
      },
      {
        id: "ai-2",
        country: "Japan",
        region: "Tokyo",
        author: "AI Assistant",
        title: "Tokyo's Smart Cycling Infrastructure System",
        content: "Tokyo implemented a smart cycling system that uses IoT sensors and AI to optimize bike lane usage and safety. The system includes: real-time traffic monitoring for cyclists, dynamic bike lane signals that adjust timing based on cyclist volume, automated maintenance alerts for bike infrastructure, and integration with mobile apps for route planning. This has reduced cycling accidents by 35% and increased bike usage during peak hours by 28%.",
        regulationType: "case-study",
        timestamp: "Generated now",
        source: "https://www.metro.tokyo.lg.jp/english/topics/2019/1016_00.html",
        isAIGenerated: true
      },
      {
        id: "ai-3",
        country: "Colombia",
        region: "Bogotá",
        author: "AI Assistant",
        title: "Bogotá's Ciclovía Program Success",
        content: "Bogotá's Ciclovía program closes over 120 kilometers of roads to cars every Sunday, creating the world's largest car-free cycling network. Started in 1974, this initiative now attracts 2 million participants weekly. The program has been replicated in 400+ cities worldwide. Benefits include: improved public health outcomes, reduced air pollution on program days, economic benefits to local businesses along routes, and strong community building through shared public space.",
        regulationType: "experience",
        timestamp: "Generated now",
        source: "https://bogota.gov.co/mi-ciudad/movilidad/ciclovia-bogotana",
        isAIGenerated: true
      }
    ];
  }
  
  if (topicLower.includes('garden') || topicLower.includes('community') || topicLower.includes('food')) {
    return [
      {
        id: "ai-4",
        country: "Cuba",
        region: "Havana",
        author: "AI Assistant",
        title: "Urban Agriculture Revolution in Havana",
        content: "Cuba's Special Period economic crisis in the 1990s led to innovative urban agriculture programs. Havana now has over 8,000 urban farms producing 50% of the city's vegetables. The program includes: technical training for urban farmers, organic farming requirements to protect urban environments, state support for agricultural cooperatives, and integration of farming into urban planning. This system provides food security while creating green spaces and employment opportunities.",
        regulationType: "case-study",
        timestamp: "Generated now",
        source: "https://www.fao.org/3/ac675e/ac675e00.htm",
        isAIGenerated: true
      },
      {
        id: "ai-5",
        country: "Germany",
        region: "Berlin",
        author: "AI Assistant",
        title: "Berlin's Kleingarten (Allotment Garden) System",
        content: "Berlin's allotment garden system includes over 67,000 individual plots across 830 sites, managed by strict legal frameworks. The Federal Allotment Garden Act ensures affordable access and sustainable practices. Key features include: long-term lease security for plot holders, environmental regulations for pesticide-free cultivation, social spaces for community building, and protection from urban development. These gardens provide fresh food access and green space for 3% of Berlin's population.",
        regulationType: "law",
        timestamp: "Generated now",
        source: "https://www.gesetze-im-internet.de/bkleing/BJNR021630983.html",
        isAIGenerated: true
      }
    ];
  }
  
  if (topicLower.includes('wifi') || topicLower.includes('digital') || topicLower.includes('library') || topicLower.includes('internet')) {
    return [
      {
        id: "ai-6",
        country: "Estonia",
        region: "Nationwide",
        author: "AI Assistant",
        title: "Estonia's Universal Internet Access Initiative",
        content: "Estonia declared internet access a human right in 2000 and achieved 99% digital coverage by 2020. The program includes: free WiFi in all public spaces and transport, digital literacy training mandatory in schools, government services available 99% online, and cybersecurity education for all citizens. The initiative has created a fully digital society where 95% of government services are accessible online, and Estonia ranks #1 globally in digital competitiveness.",
        regulationType: "policy",
        timestamp: "Generated now",
        source: "https://e-estonia.com/solutions/connectivity/",
        isAIGenerated: true
      },
      {
        id: "ai-7",
        country: "South Korea",
        region: "Seoul",
        author: "AI Assistant",
        title: "Seoul's Digital Inclusion Program for Seniors",
        content: "Seoul's comprehensive digital inclusion program serves over 200,000 seniors annually through 25 Digital Media Centers. The program offers: free smartphone and computer training, one-on-one tutoring sessions, simplified technology interfaces designed for seniors, and family digital literacy programs. Results show 78% of participating seniors now use digital banking services, and 65% actively use social media to connect with family. The program has reduced digital isolation during the COVID-19 pandemic.",
        regulationType: "case-study",
        timestamp: "Generated now",
        source: "https://english.seoul.go.kr/policy/welfare/digital-inclusion/",
        isAIGenerated: true
      }
    ];
  }
  
  // Default perspectives for other topics
  return [
    {
      id: "ai-default-1",
      country: "Finland",
      region: "Helsinki",
      author: "AI Assistant",
      title: "Finland's Participatory Democracy Model",
      content: "Finland has implemented extensive citizen participation mechanisms in policy-making. The system includes: citizen panels for major policy decisions, online platforms for public consultation on legislation, mandatory impact assessments for community proposals, and annual participatory budgeting processes. This approach has increased citizen satisfaction with government decisions by 40% and led to more effective policy implementation.",
      regulationType: "policy",
      timestamp: "Generated now",
      source: "https://www.kansalaispalvelut.fi/en",
      isAIGenerated: true
    },
    {
      id: "ai-default-2",
      country: "New Zealand",
      region: "Wellington",
      author: "AI Assistant",
      title: "Wellbeing Budget Approach to Policy",
      content: "New Zealand pioneered the 'Wellbeing Budget' approach, where all government spending is evaluated based on four wellbeing domains: mental health, child poverty, indigenous inequality, and climate change. This framework ensures policy decisions consider long-term community impact rather than just economic factors. The approach has led to more holistic policy solutions and improved coordination between government departments.",
      regulationType: "experience",
      timestamp: "Generated now",
      source: "https://treasury.govt.nz/information-and-services/financial-management-and-advice/wellbeing-budget",
      isAIGenerated: true
    }
  ];
}