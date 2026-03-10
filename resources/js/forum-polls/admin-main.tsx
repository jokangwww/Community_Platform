import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { AdminDashboardPage } from "./pages/AdminDashboardPage";

const authUser = (window as any).authUser ?? {};
const adminId = authUser.id?.toString() ?? "";
const adminNickname = authUser.nickname ?? authUser.name ?? "Admin";
const initialTab: string = (window as any).adminInitialTab ?? "forum";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <AdminDashboardPage
      adminId={adminId}
      adminNickname={adminNickname}
      initialTab={initialTab}
      onBack={() => { window.location.href = "/admin"; }}
      onViewContent={(contentId, contentType) => {
        if (contentType === "poll") {
          window.location.href = `/poll-petition?tab=polls&viewId=${contentId}`;
        } else if (contentType === "petition") {
          window.location.href = `/poll-petition?tab=petitions&viewId=${contentId}`;
        } else {
          window.location.href = `/forum?viewPost=${contentId}`;
        }
      }}
      onSwitchToUser={() => { window.location.href = "/forum"; }}
    />
  </StrictMode>
);
