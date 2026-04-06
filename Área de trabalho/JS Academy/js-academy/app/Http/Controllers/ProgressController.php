<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Progress;
use App\Models\Track;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * ProgressController: Gerencia a gamificação, progresso de aulas e conquistas.
 */
class ProgressController extends Controller
{
    /**
     * Marca uma lição como concluída e atribui XP/Badges.
     */
    public function completeLesson(Request $request, $lessonId)
    {
        // Prioriza o usuário logado, caso contrário usa o primeiro (fallback para testes)
        $user = auth()->user() ?: User::find(1);
        $lesson = Lesson::findOrFail($lessonId);

        // Registra ou recupera o progresso da lição para este usuário
        $progress = Progress::firstOrCreate([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        // Se ainda não estava concluída, processa a recompensa
        if ($progress->status !== 'completed') {
            $progress->status = 'completed';
            $progress->save();

            // Atualiza o XP do usuário
            $this->updateXPLogic($user, $lesson->xp_reward);

            // Verifica se a trilha (Track) foi finalizada para dar a Badge
            $track = $lesson->track;
            $totalLessons = $track->lessons()->count();
            $completedLessons = Progress::where('user_id', $user->id)
                ->whereIn('lesson_id', $track->lessons()->pluck('id'))
                ->where('status', 'completed')
                ->count();

            // Se concluiu todas as aulas da trilha, ganha a conquista (Badge)
            if ($totalLessons > 0 && $completedLessons === $totalLessons) {
                $badge = $track->badge;
                if ($badge && ! $user->badges()->where('badge_id', $badge->id)->exists()) {
                    $user->badges()->attach($badge->id);
                }
            }
        }

        return response()->json([
            'success' => true,
            'xp' => $user->xp,
            'badges' => $user->badges()->get(),
        ]);
    }

    /**
     * Lógica central de XP, Nível e Ofensiva (Streak).
     */
    private function updateXPLogic($user, $xpToAdd)
    {
        $user->xp += $xpToAdd;
        // Sobe de nível a cada 1000 de XP
        $user->level = (int) floor($user->xp / 1000) + 1;

        // Lógica de Ofensiva (Estudar todos os dias)
        $today = Carbon::today();
        $lastCompleted = $user->streak_last_completed_at ? Carbon::parse($user->streak_last_completed_at) : null;

        if (! $lastCompleted) {
            $user->streak = 1;
        } elseif ($lastCompleted->isToday()) {
            // Já estudou hoje, mantém a ofensiva
        } elseif ($lastCompleted->diffInDays($today) == 1) {
            // Estudou o dia seguinte, sobe a ofensiva
            $user->streak += 1;
        } else {
            // Pulou dias: verifica se tem "Congelamento de Ofensiva" (Streak Freeze)
            if ($user->streak_freeze_count > 0) {
                $user->streak_freeze_count -= 1;
            } else {
                $user->streak = 1; // Reinicia a ofensiva
            }
        }

        $user->streak_last_completed_at = $today;
        $user->save();
    }

    /**
     * Endpoint manual para atualização de XP (usado em desafios).
     */
    public function updateXP(Request $request)
    {
        $user = auth()->user() ?: User::find(1);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Usuário não encontrado'], 404);
        }

        $xpToAdd = (int) $request->input('xp', 100);
        $this->updateXPLogic($user, $xpToAdd);

        return response()->json([
            'success' => true,
            'xp' => $user->xp,
            'level' => $user->level,
            'streak' => $user->streak,
            'diamonds' => $user->diamonds,
        ]);
    }

    /**
     * Compra um congelamento de ofensiva usando diamantes.
     */
    public function freezeStreak()
    {
        $user = auth()->user() ?: User::first();
        if ($user->diamonds >= 50) {
            $user->diamonds -= 50;
            $user->streak_freeze_count += 1;
            $user->save();

            return response()->json(['success' => true, 'diamonds' => $user->diamonds]);
        }

        return response()->json(['success' => false, 'message' => 'Diamantes insuficientes'], 400);
    }

    /**
     * Pula um módulo (atribui badge mas não XP das lições restantes).
     */
    public function skipTrack($trackId)
    {
        $user = auth()->user() ?: User::find(1);
        $track = Track::findOrFail($trackId);

        $badge = $track->badge;
        if ($badge && ! $user->badges()->where('badge_id', $badge->id)->exists()) {
            $user->badges()->attach($badge->id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Retorna o ranking Top 10 para o widget de Pulse.
     */
    public function ranking()
    {
        $users = User::orderBy('xp', 'desc')->take(10)->get(['name', 'xp', 'league']);

        return response()->json($users);
    }
}
