import { useState } from "react";
import { AdminDashboard } from "../components/shared/AdminDashboard";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { ArrowLeft, Shield, User } from "lucide-react";

interface AdminDashboardPageProps {
  adminId: string;
  adminNickname: string;
  onBack: () => void;
  onViewContent: (contentId: string, contentType: string) => void;
  onSwitchToUser: () => void;
  initialTab?: string;
}

export function AdminDashboardPage({
  adminId,
  adminNickname,
  onBack,
  onViewContent,
  onSwitchToUser,
  initialTab
}: AdminDashboardPageProps) {
  return (
    <div className="min-h-screen bg-white">
      {/* Header with Back Button */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Button
                variant="ghost"
                onClick={onBack}
                className="flex items-center gap-2 text-white hover:bg-blue-700 hover:text-white cursor-pointer"
              >
                <ArrowLeft className="h-4 w-4" />
                Back
              </Button>
              <div className="h-6 w-px bg-blue-400" />
              <div className="flex items-center gap-3">
                <Shield className="h-6 w-6 text-white" />
                <div>
                  <h1 className="font-semibold text-white">Admin Moderation Panel</h1>
                  <p className="text-sm text-blue-100">Moderator: {adminNickname}</p>
                </div>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <Button
                onClick={onSwitchToUser}
                variant="outline"
                className="flex items-center gap-2 bg-white text-blue-700 border-white hover:bg-blue-50"
              >
                <User className="h-4 w-4" />
                Switch to User Side
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Dashboard Content */}
      <div className="bg-white">
        <AdminDashboard
          adminId={adminId}
          onViewContent={onViewContent}
          defaultTab={initialTab}
        />
      </div>
    </div>
  );
}