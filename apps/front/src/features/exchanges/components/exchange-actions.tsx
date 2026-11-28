import type { Exchange } from '../queries'
import type { StartWith } from './exchange-detail'
import { ExchangeGestures } from './exchange-gestures'
import { JoinTheVideoCall } from './join-the-video-call'

type Props = { exchange: Exchange; onOpen: (startWith: StartWith) => void }

/** What a timeline's line offers at hand: joining its video call, then the gestures its links offer. */
export function ExchangeActions({ exchange, onOpen }: Props) {
  return (
    <>
      <JoinTheVideoCall exchange={exchange} />
      <ExchangeGestures exchange={exchange} onOpen={onOpen} />
    </>
  )
}
