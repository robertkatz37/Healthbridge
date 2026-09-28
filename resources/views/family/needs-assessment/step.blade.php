<x-wizard-layout title="Needs Assessment — {{ $careSeeker->full_name }}" :current-step="$step">

    @php
        $stepTitles = [
            1 => ['Care Type & Timeline', 'What kind of care are you looking for, and when?'],
            2 => ['Budget & Location', 'Help us understand your budget and preferred area.'],
            3 => ['Health & Mobility', 'Tell us about medical conditions, mobility, and memory care needs.'],
            4 => ['Daily Living & Behavior', 'What daily support is needed, and any behavioral considerations?'],
            5 => ['Languages & Coverage', 'Languages spoken, insurance, and veteran benefits.'],
        ];
        [$stepTitle, $stepSubtitle] = $stepTitles[$step] ?? ['Assessment', ''];
    @endphp

    <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color:var(--hb-gray-900);font-family:'Fraunces',serif;">{{ $stepTitle }}</h4>
        <p style="color:var(--hb-gray-600);font-size:0.9rem;">{{ $stepSubtitle }} <span class="text-muted">— for {{ $careSeeker->full_name }}</span></p>
    </div>

    <form method="POST" action="{{ route('family.needs-assessment.step.store', [$careSeeker, $step]) }}" novalidate
          x-data="assessmentStep({{ \Illuminate\Support\Js::from($existingAnswers) }})">
        @csrf

        @foreach($questions as $question)
            @php
                $condition = $question->display_condition;
                $showAttr = $condition ? "shouldShow(" . \Illuminate\Support\Js::from($condition) . ")" : null;
            @endphp
            <div class="mb-4" @if($showAttr) x-show="{{ $showAttr }}" x-cloak @endif>
                <label class="hb-form-label">{{ $question->question_text }}</label>

                @if($question->input_type === 'text')
                    <textarea name="answers[{{ $question->code }}]" rows="2" class="hb-form-control"
                              x-model="answers['{{ $question->code }}']"></textarea>

                @elseif($question->input_type === 'number')
                    <input type="number" name="answers[{{ $question->code }}]" class="hb-form-control" step="0.01" min="0"
                           x-model="answers['{{ $question->code }}']">

                @elseif($question->input_type === 'single_select')
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($question->options as $option)
                            @php
                                $val = is_array($option) ? $option['value'] : $option;
                                $label = is_array($option) ? $option['label'] : ucfirst(str_replace('_', ' ', $option));
                            @endphp
                            <label class="hb-role-card" style="padding:0.65rem 1rem;flex:0 0 auto;"
                                   :class="{ selected: answers['{{ $question->code }}'] === '{{ $val }}' }">
                                <input type="radio" name="answers[{{ $question->code }}]" value="{{ $val }}"
                                       x-model="answers['{{ $question->code }}']">
                                <span style="font-size:0.85rem;font-weight:500;">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                @elseif($question->input_type === 'multi_select')
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($question->options as $option)
                            @php
                                $val = is_array($option) ? $option['value'] : $option;
                                $label = is_array($option) ? $option['label'] : ucfirst(str_replace('_', ' ', $option));
                            @endphp
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="answers[{{ $question->code }}][]" value="{{ $val }}"
                                       id="opt_{{ $question->code }}_{{ $val }}"
                                       :checked="(answers['{{ $question->code }}'] || []).includes('{{ $val }}')"
                                       @change="toggleMulti('{{ $question->code }}', '{{ $val }}')">
                                <label class="form-check-label" for="opt_{{ $question->code }}_{{ $val }}" style="font-size:0.875rem;">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        @if($questions->isEmpty())
            <p style="color:var(--hb-gray-600);font-size:0.875rem;">No questions in this section.</p>
        @endif

        <div class="d-flex justify-content-between mt-4">
            @if($step > 1)
                <a href="{{ route('family.needs-assessment.step', [$careSeeker, $step - 1]) }}" class="hb-btn-outline" style="width:auto;padding:0.7rem 1.75rem;text-decoration:none;">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            @else
                <span></span>
            @endif
            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.7rem 2rem;">
                Continue <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        function assessmentStep(initial) {
            return {
                answers: initial || {},
                shouldShow(condition) {
                    if (!condition || !condition.question) return true;
                    const actual = this.answers[condition.question];
                    const expected = condition.value;
                    if (condition.operator === '!=') return actual !== expected;
                    return actual === expected;
                },
                toggleMulti(code, value) {
                    if (!this.answers[code]) this.answers[code] = [];
                    const idx = this.answers[code].indexOf(value);
                    if (idx > -1) {
                        this.answers[code].splice(idx, 1);
                    } else {
                        this.answers[code].push(value);
                    }
                },
            };
        }
    </script>
    @endpush
</x-wizard-layout>
