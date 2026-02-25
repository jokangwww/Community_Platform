import { useState } from "react";
import { UserDashboard } from "../components/shared/UserDashboard";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { ArrowLeft, Shield } from "lucide-react";

interface UserDashboardPageProps {
  userId: string;
  userNickname: string;
  onBack: () => void;
  onPostClick: (postId: string) => void;
  onSwitchToAdmin: () => void;
}

export function UserDashboardPage({
  userId,
  userNickname,
  onBack,
  onPostClick,
  onSwitchToAdmin
}: UserDashboardPageProps) {
  return (
    <div className="min-h-screen bg-white">
      {/* Header with Back Button */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
        <div className="max-w-7xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="font-semibold text-gray-900">My Dashboard</h1>
              <p className="text-sm text-gray-600">Welcome back, {userNickname}</p>
            </div>
            <div className="flex items-center gap-3">
              <Button
                onClick={onSwitchToAdmin}
                variant="outline"
                className="flex items-center gap-2 border-blue-600 text-blue-600 hover:bg-blue-50"
              >
                <Shield className="h-4 w-4" />
                Switch to Admin Dashboard
              </Button>
              <Button
                variant="outline"
                onClick={onBack}
                className="flex items-center gap-2"
              >
                <ArrowLeft className="h-4 w-4" />
                Back
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Dashboard Content */}
      <div className="bg-white">
        <UserDashboard
          userId={userId}
          userNickname={userNickname}
          onPostClick={onPostClick}
        />
      </div>
    </div>
  );
}