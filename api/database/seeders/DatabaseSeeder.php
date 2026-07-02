<?php

namespace Database\Seeders;

use App\Enums\AccountState;
use App\Enums\DecisionOutcome;
use App\Enums\DocumentKind;
use App\Enums\HistoryEventType;
use App\Enums\MessageKind;
use App\Enums\NotificationType;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Decision;
use App\Models\Document;
use App\Models\Message;
use App\Models\Notification;
use App\Models\OrganizationSettings;
use App\Models\Request;
use App\Models\RequestCategory;
use App\Models\RequestHistoryEntry;
use App\Models\UserAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic dev fixture (§5.7). Spans every value set: a user per Role plus
 * an inactive account; active and inactive categories; the singleton settings;
 * a request in each RequestStatus; supporting and decision documents; a decision
 * per DecisionOutcome; a message per MessageKind; a history entry per
 * HistoryEventType; a notification per NotificationType.
 *
 * Idempotent: re-running leaves the seeded data intact (§10).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (UserAccount::where('email', 'admin@permitflow.test')->exists()) {
            return; // Already seeded — leave data intact (§10).
        }

        $now = now();

        // --- User accounts: one per role + an inactive account ---
        $admin = UserAccount::create([
            'display_name' => 'Ada Administrator',
            'email' => 'admin@permitflow.test',
            'role' => Role::Administrator,
            'account_state' => AccountState::Active,
            'password' => Hash::make('password'),
        ]);

        $staff1 = UserAccount::create([
            'display_name' => 'Sam Staff',
            'email' => 'staff1@permitflow.test',
            'role' => Role::StaffMember,
            'account_state' => AccountState::Active,
            'password' => Hash::make('password'),
        ]);

        $staff2 = UserAccount::create([
            'display_name' => 'Sara Staff',
            'email' => 'staff2@permitflow.test',
            'role' => Role::StaffMember,
            'account_state' => AccountState::Active,
            'password' => Hash::make('password'),
        ]);

        $citizen1 = UserAccount::create([
            'display_name' => 'Cleo Citizen',
            'email' => 'citizen1@permitflow.test',
            'role' => Role::Citizen,
            'account_state' => AccountState::Active,
            'password' => Hash::make('password'),
        ]);

        $citizen2 = UserAccount::create([
            'display_name' => 'Carl Citizen',
            'email' => 'citizen2@permitflow.test',
            'role' => Role::Citizen,
            'account_state' => AccountState::Active,
            'password' => Hash::make('password'),
        ]);

        UserAccount::create([
            'display_name' => 'Iris Inactive',
            'email' => 'inactive@permitflow.test',
            'role' => Role::Citizen,
            'account_state' => AccountState::Inactive,
            'password' => Hash::make('password'),
        ]);

        // --- Request categories: active + inactive ---
        $buildingPermit = RequestCategory::create([
            'name' => 'Building Permit',
            'description' => 'Construction and renovation permits.',
            'is_active' => true,
        ]);

        RequestCategory::create([
            'name' => 'Legacy Category',
            'description' => 'Retired classification, kept for history.',
            'is_active' => false,
        ]);

        // --- Organization settings: the singleton ---
        OrganizationSettings::create([
            'singleton_key' => 'single_organization',
            'organization_name' => 'City of PermitFlow',
            'settings_payload' => ['locale' => 'en', 'timezone' => 'UTC'],
            'updated_by_user_account_id' => $admin->id,
        ]);

        // --- Requests: one per status ---
        $draftReq = Request::create([
            'owner_user_account_id' => $citizen1->id,
            'request_category_id' => $buildingPermit->id,
            'responsible_staff_user_account_id' => null,
            'title' => 'Garage extension (draft)',
            'request_details' => ['summary' => 'Adding a single-car garage.'],
            'status' => RequestStatus::Draft,
            'submitted_at' => null,
        ]);

        $submittedReq = Request::create([
            'owner_user_account_id' => $citizen1->id,
            'request_category_id' => $buildingPermit->id,
            'responsible_staff_user_account_id' => null,
            'title' => 'Fence permit (submitted)',
            'request_details' => ['summary' => 'New boundary fence.'],
            'status' => RequestStatus::Submitted,
            'submitted_at' => $now,
        ]);

        $inReviewReq = Request::create([
            'owner_user_account_id' => $citizen1->id,
            'request_category_id' => $buildingPermit->id,
            'responsible_staff_user_account_id' => $staff1->id,
            'title' => 'Roof solar panels (in review)',
            'request_details' => ['summary' => 'Rooftop PV installation.'],
            'status' => RequestStatus::InReview,
            'submitted_at' => $now,
        ]);

        $waitingReq = Request::create([
            'owner_user_account_id' => $citizen2->id,
            'request_category_id' => $buildingPermit->id,
            'responsible_staff_user_account_id' => $staff1->id,
            'title' => 'Cafe terrace (waiting for citizen)',
            'request_details' => ['summary' => 'Outdoor seating area.'],
            'status' => RequestStatus::WaitingForCitizen,
            'submitted_at' => $now,
        ]);

        $readyReq = Request::create([
            'owner_user_account_id' => $citizen2->id,
            'request_category_id' => $buildingPermit->id,
            'responsible_staff_user_account_id' => $staff1->id,
            'title' => 'Sign installation (ready for decision)',
            'request_details' => ['summary' => 'Illuminated shop sign.'],
            'status' => RequestStatus::ReadyForDecision,
            'submitted_at' => $now,
        ]);

        $decidedApprovedReq = Request::create([
            'owner_user_account_id' => $citizen1->id,
            'request_category_id' => $buildingPermit->id,
            'responsible_staff_user_account_id' => $staff1->id,
            'title' => 'Driveway widening (decided/approved)',
            'request_details' => ['summary' => 'Widen existing driveway.'],
            'status' => RequestStatus::Decided,
            'submitted_at' => $now,
        ]);

        $decidedRejectedReq = Request::create([
            'owner_user_account_id' => $citizen2->id,
            'request_category_id' => $buildingPermit->id,
            'responsible_staff_user_account_id' => $staff2->id,
            'title' => 'Demolition (decided/rejected)',
            'request_details' => ['summary' => 'Demolish rear outbuilding.'],
            'status' => RequestStatus::Decided,
            'submitted_at' => $now,
        ]);

        // --- Decisions: one per outcome ---
        $decApproved = Decision::create([
            'request_id' => $decidedApprovedReq->id,
            'decided_by_user_account_id' => $staff1->id,
            'outcome' => DecisionOutcome::Approved,
            'decision_text' => 'Approved subject to standard conditions.',
            'decided_at' => $now,
        ]);

        Decision::create([
            'request_id' => $decidedRejectedReq->id,
            'decided_by_user_account_id' => $staff2->id,
            'outcome' => DecisionOutcome::Rejected,
            'decision_text' => 'Rejected: insufficient structural assessment.',
            'decided_at' => $now,
        ]);

        // --- Documents: supporting + decision ---
        Document::create([
            'request_id' => $inReviewReq->id,
            'uploaded_by_user_account_id' => $citizen1->id,
            'decision_id' => null,
            'kind' => DocumentKind::Supporting,
            'file_reference' => 'documents/supporting-roof-plan.pdf',
            'original_filename' => 'roof-plan.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 245_760,
            'uploaded_at' => $now,
            'description' => 'Roof layout supporting the PV request.',
        ]);

        $decisionDoc = Document::create([
            'request_id' => $decidedApprovedReq->id,
            'uploaded_by_user_account_id' => $staff1->id,
            'decision_id' => $decApproved->id,
            'kind' => DocumentKind::Decision,
            'file_reference' => 'documents/decision-driveway.pdf',
            'original_filename' => 'decision.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 51_200,
            'uploaded_at' => $now,
            'description' => 'Signed decision document.',
        ]);

        // --- Messages: one per kind ---
        $msgGeneral = Message::create([
            'request_id' => $inReviewReq->id,
            'sender_user_account_id' => $citizen1->id,
            'recipient_user_account_id' => $staff1->id,
            'message_kind' => MessageKind::General,
            'body' => 'Happy to provide anything else you need.',
            'sent_at' => $now,
        ]);

        $msgMissing = Message::create([
            'request_id' => $waitingReq->id,
            'sender_user_account_id' => $staff1->id,
            'recipient_user_account_id' => $citizen2->id,
            'message_kind' => MessageKind::MissingInformationRequest,
            'body' => 'Please provide the site boundary plan.',
            'sent_at' => $now,
        ]);

        $msgReply = Message::create([
            'request_id' => $waitingReq->id,
            'sender_user_account_id' => $citizen2->id,
            'recipient_user_account_id' => $staff1->id,
            'message_kind' => MessageKind::CitizenReply,
            'body' => 'Boundary plan attached as requested.',
            'sent_at' => $now,
        ]);

        // --- Request history entries: one per event type (sequence per request) ---
        $heStatus = RequestHistoryEntry::create([
            'request_id' => $submittedReq->id,
            'sequence_number' => 1,
            'actor_user_account_id' => $citizen1->id,
            'event_type' => HistoryEventType::StatusChanged,
            'from_status' => RequestStatus::Draft,
            'to_status' => RequestStatus::Submitted,
            'summary' => 'Citizen submitted the request.',
            'event_occurred_at' => $now,
        ]);

        $heAssign = RequestHistoryEntry::create([
            'request_id' => $inReviewReq->id,
            'sequence_number' => 1,
            'actor_user_account_id' => $admin->id,
            'new_staff_user_account_id' => $staff1->id,
            'event_type' => HistoryEventType::AssignmentChanged,
            'summary' => 'Administrator assigned the request to Sam Staff.',
            'event_occurred_at' => $now,
        ]);

        $heMessage = RequestHistoryEntry::create([
            'request_id' => $inReviewReq->id,
            'sequence_number' => 2,
            'actor_user_account_id' => $citizen1->id,
            'message_id' => $msgGeneral->id,
            'event_type' => HistoryEventType::MessageRecorded,
            'summary' => 'Citizen sent a message.',
            'event_occurred_at' => $now,
        ]);

        $heInfoReq = RequestHistoryEntry::create([
            'request_id' => $waitingReq->id,
            'sequence_number' => 1,
            'actor_user_account_id' => $staff1->id,
            'message_id' => $msgMissing->id,
            'event_type' => HistoryEventType::InformationRequested,
            'from_status' => RequestStatus::InReview,
            'to_status' => RequestStatus::WaitingForCitizen,
            'summary' => 'Staff requested missing information.',
            'event_occurred_at' => $now,
        ]);

        $heInfoProv = RequestHistoryEntry::create([
            'request_id' => $waitingReq->id,
            'sequence_number' => 2,
            'actor_user_account_id' => $citizen2->id,
            'message_id' => $msgReply->id,
            'event_type' => HistoryEventType::InformationProvided,
            'summary' => 'Citizen provided the requested information.',
            'event_occurred_at' => $now,
        ]);

        $heDecision = RequestHistoryEntry::create([
            'request_id' => $decidedApprovedReq->id,
            'sequence_number' => 1,
            'actor_user_account_id' => $staff1->id,
            'decision_id' => $decApproved->id,
            'document_id' => $decisionDoc->id,
            'event_type' => HistoryEventType::DecisionRecorded,
            'from_status' => RequestStatus::ReadyForDecision,
            'to_status' => RequestStatus::Decided,
            'summary' => 'Staff recorded an approved decision.',
            'event_occurred_at' => $now,
        ]);

        // --- Notifications: one per type ---
        Notification::create([
            'recipient_user_account_id' => $admin->id,
            'request_id' => $submittedReq->id,
            'request_history_entry_id' => $heStatus->id,
            'notification_type' => NotificationType::RequestSubmitted,
            'body' => 'A new request was submitted.',
        ]);

        Notification::create([
            'recipient_user_account_id' => $staff1->id,
            'request_id' => $inReviewReq->id,
            'request_history_entry_id' => $heAssign->id,
            'notification_type' => NotificationType::Assigned,
            'body' => 'You were assigned a request.',
        ]);

        Notification::create([
            'recipient_user_account_id' => $staff2->id,
            'request_id' => $decidedRejectedReq->id,
            'notification_type' => NotificationType::Reassigned,
            'body' => 'A request was reassigned to you.',
        ]);

        Notification::create([
            'recipient_user_account_id' => $citizen2->id,
            'request_id' => $waitingReq->id,
            'request_history_entry_id' => $heInfoReq->id,
            'notification_type' => NotificationType::MissingInformationRequested,
            'body' => 'Missing information was requested on your request.',
        ]);

        Notification::create([
            'recipient_user_account_id' => $staff1->id,
            'request_id' => $waitingReq->id,
            'request_history_entry_id' => $heInfoProv->id,
            'notification_type' => NotificationType::InformationProvided,
            'body' => 'The citizen provided requested information.',
        ]);

        Notification::create([
            'recipient_user_account_id' => $citizen1->id,
            'request_id' => $submittedReq->id,
            'request_history_entry_id' => $heStatus->id,
            'notification_type' => NotificationType::StatusChanged,
            'body' => 'Your request status changed.',
        ]);

        Notification::create([
            'recipient_user_account_id' => $citizen1->id,
            'request_id' => $decidedApprovedReq->id,
            'request_history_entry_id' => $heDecision->id,
            'notification_type' => NotificationType::DecisionRecorded,
            'body' => 'A decision was recorded on your request.',
        ]);

        Notification::create([
            'recipient_user_account_id' => $staff1->id,
            'request_id' => $inReviewReq->id,
            'request_history_entry_id' => $heMessage->id,
            'notification_type' => NotificationType::MessageReceived,
            'body' => 'You received a message on a request.',
        ]);
    }
}
